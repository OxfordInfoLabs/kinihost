<?php

namespace Kinihost\Services\Site;

use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Communication\Email\SuperUserTemplatedEmail;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteConfig;
use Kinihost\Objects\Site\SiteDomain;
use Kinihost\Objects\Site\SiteSummary;
use Kinihost\ValueObjects\Site\SiteDescriptor;
use Kinihost\ValueObjects\Site\SiteSettings;
use Kinihost\TestBase;

include_once "autoloader.php";

class SiteServiceTest extends TestBase {

    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var QueuedTaskService
     */
    private $queuedTaskService;

    /**
     * @var MockObject
     */
    private $emailService;

    public function setUp(): void {
        $this->queuedTaskService = Container::instance()->get(QueuedTaskService::class);

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->emailService = $mockObjectProvider->getMockInstance(EmailService::class);

        $this->siteService = Container::instance()->get(SiteService::class);
        $this->siteService->setEmailService($this->emailService);

        $this->authenticationService = Container::instance()->get(AuthenticationService::class);

    }


    public function testCanListAllSitesWithFilteringAndLimitsAndOffsets() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $sites = $this->siteService->listSites();


        $this->assertTrue(sizeof($sites) > 5);
        $this->assertInstanceOf(SiteSummary::class, $sites[0]);
        $this->assertEquals("lucientaylor", $sites[0]->getSiteKey());


        $sites = $this->siteService->listSites("p");

        $this->assertGreaterThan(1, sizeof($sites));

        // Ordering by title not site key
        $this->assertEquals("pingu", $sites[0]->getSiteKey());
        $this->assertEquals("paperchase", $sites[1]->getSiteKey());

        // Limits
        $sites = $this->siteService->listSites("", 0, 5);
        $this->assertEquals(5, sizeof($sites));
        $this->assertEquals("lucientaylor", $sites[0]->getSiteKey());

        // Offsets
        $sites = $this->siteService->listSites("", 1, 5);
        $this->assertEquals(5, sizeof($sites));
        $this->assertEquals("markrobertshaw", $sites[0]->getSiteKey());

    }


    public function testCanListAllSitesForAccount() {

        // Do a security check
        AuthenticationHelper::logout();

        $this->assertEquals([], $this->siteService->listSitesForAccount(1));
        $this->assertEquals([], $this->siteService->listSitesForAccount(5));

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");
        $sites = $this->siteService->listSitesForAccount("", 0, 10, 1);
        $this->assertTrue(sizeof($sites) >= 6);

        $siteKeys = ObjectArrayUtils::getMemberValueArrayForObjects("siteKey", $sites);
        $this->assertContains("lucientaylor", $siteKeys);
        $this->assertContains("markrobertshaw", $siteKeys);
        $this->assertContains("nathanalan", $siteKeys);
        $this->assertContains("pingu", $siteKeys);
        $this->assertContains("samdavisdotcom", $siteKeys);
        $this->assertContains("woollenmill", $siteKeys);
        $this->assertNotContains("smartcoasting", $siteKeys);

        $sites = $this->siteService->listSitesForAccount("", 0, 10, 5);
        $this->assertEquals(1, sizeof($sites));
        $this->assertEquals("smartcoasting", $sites[0]->getSiteKey());


    }


    public function testCanCheckWhetherSiteKeyIsAvailable() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $this->assertTrue($this->siteService->isSiteKeyAvailable("myfirstsite"));
        $this->assertTrue($this->siteService->isSiteKeyAvailable("mysecondsite"));

        $this->assertFalse($this->siteService->isSiteKeyAvailable("samdavisdotcom"));
        $this->assertFalse($this->siteService->isSiteKeyAvailable("woollenmill"));
        $this->assertFalse($this->siteService->isSiteKeyAvailable("pingu"));
        $this->assertFalse($this->siteService->isSiteKeyAvailable("smartcoasting"));

        $this->assertFalse($this->siteService->isSiteKeyAvailable("Smart Fishing"));
        $this->assertFalse($this->siteService->isSiteKeyAvailable("name-with@otherchars"));


    }

    public function testCanGetSuggestedSiteKeyForTitle() {
        $this->assertEquals("myfirstsite", $this->siteService->getSuggestedSiteKey("My First Site"));
        $this->assertEquals("anewsite", $this->siteService->getSuggestedSiteKey("AN %£@-- e ___w*** SITE"));
        $this->assertEquals("samdavisdotcom2", $this->siteService->getSuggestedSiteKey("Sam Davis Dot Com"));
        $this->assertEquals("woollenmill2", $this->siteService->getSuggestedSiteKey("WOOLLEN MILL"));
    }


    public function testCanCreateNewSiteWithDescriptor() {

        // Create descriptor for new site
        $newSite = new SiteDescriptor("Mary Poppins Hair Display");


        // Confirm we can't create unauthenticated or across accounts.
        AuthenticationHelper::logout();
        try {
            $this->siteService->createSite($newSite, 1);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");
        try {
            $this->siteService->createSite($newSite, 1);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            // Success
        }


        // Do real one.
        $site = $this->siteService->createSite($newSite);
        $this->assertNotNull($site->getSiteId());
        $this->assertEquals("marypoppinshairdispl", $site->getSiteKey());
        $this->assertEquals("Mary Poppins Hair Display", $site->getTitle());


        // Check logged in user was granted full access to the site.
        UserRole::fetch([
            3, "SITE", $site->getSiteId(), 5
        ]);

        UserRole::fetch([
            3, "SITE", $site->getSiteId(), 6
        ]);


        // Check email was sent out
        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new AccountTemplatedEmail(2, "setting-up",
                ["site" => $site])
        ]));


        // Check can't create second one with same site key.
        $newSite = new SiteDescriptor("Second Site", "marypoppinshairdispl");
        try {
            $this->siteService->createSite($newSite);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            // Success
        }

    }


    public function testCanCreateSiteOwnedBySuperAdmin() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        // Create descriptor for new site
        $newSite = new SiteDescriptor("Bob Jones Admin Site");


        // Do real one.
        $site = $this->siteService->createSite($newSite, 0);
        $this->assertNotNull($site->getSiteId());
        $this->assertEquals("bobjonesadminsite", $site->getSiteKey());
        $this->assertEquals("Bob Jones Admin Site", $site->getTitle());

    }


    public function testCanGetSiteByKeyIfPermissions() {

        // Confirm we can't access as guest
        AuthenticationHelper::logout();
        try {
            $this->siteService->getSiteByKey("samdavisdotcom");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }


        // Login
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        // Confirm we can't access across accounts.
        try {
            $this->siteService->getSiteByKey("samdavisdotcom");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        // Now confirm administrator for account can access correctly.
        $site = $this->siteService->getSiteByKey("paperchase");
        $this->assertEquals("The Paper Chasing Machine", $site->getTitle());
        $this->assertEquals("paperchase", $site->getSiteKey());


        // Now login as another user without granted site access and confirm no access.
        AuthenticationHelper::login("regularuser@smartcoasting.org", "password");

        // Confirm we can't access samdavisdotcom as we are not in the site.
        try {
            $this->siteService->getSiteByKey("samdavisdotcom");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }


        // Now login as another user with granted site access and confirm access
        AuthenticationHelper::login("mary@shoppingonline.com", "password");


        // Now confirm administrator for account can access correctly.
        $site = $this->siteService->getSiteByKey("paperchase");
        $this->assertEquals("The Paper Chasing Machine", $site->getTitle());
        $this->assertEquals("paperchase", $site->getSiteKey());


        // Now login as super user and confirm access.
        AuthenticationHelper::login("admin@kinicart.com", "password");
        $site = $this->siteService->getSiteByKey("paperchase");
        $this->assertEquals("The Paper Chasing Machine", $site->getTitle());
        $this->assertEquals("paperchase", $site->getSiteKey());


    }


    public function testUpdateSiteUpdatesSiteNameAndReturnsSuccessStringIfValidNameChange() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $updateDescriptor = new SiteDescriptor("Penguin Graphics", "pingu");
        $response = $this->siteService->updateSite($updateDescriptor);
        $this->assertEquals("SUCCESS", $response);

        // Grab the site
        $site = $this->siteService->getSiteByKey("pingu");
        $this->assertEquals("Penguin Graphics", $site->getTitle());

    }


    public function testCanRemoveSiteByKeyIfPermissions() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        // Create descriptor for new site
        $newSite = new SiteDescriptor("My Friendly Giant");
        $this->siteService->createSite($newSite);

        // Test bad removes are caught
        try {
            $this->siteService->removeSiteByKey("mylittlebadboy");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        // Now remove a genuine one.
        $this->siteService->removeSiteByKey("myfriendlygiant");

        try {
            $this->siteService->getSiteByKey("myfriendlygiant");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        $this->assertTrue(true);


    }

    /**
     * List all site title and keys
     */
    public function testCanListSiteTitleAndKeysForUser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $sites = $this->siteService->listSiteTitlesAndKeysForUser();
        $this->assertEquals(4, sizeof($sites));
        $this->assertContains(["title" => "Sam Davis Design .COM", "siteKey" => "samdavisdotcom"], $sites);
        $this->assertContains(["title" => "Woollen Mill Site", "siteKey" => "woollenmill"], $sites);

    }


    public function testCanListRecentSitesForAccount() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create descriptor for new site
        for ($i = 0; $i < 11; $i++) {
            $newSite = new SiteDescriptor("My Recent Site $i");
            $this->siteService->createSite($newSite);
        }

        $recentSites = $this->siteService->listRecentSitesForAccount(1);
        $this->assertEquals(10, sizeof($recentSites));
        $this->assertEquals("myrecentsite10", $recentSites[0]->getSiteKey());
        $this->assertEquals("myrecentsite9", $recentSites[1]->getSiteKey());
        $this->assertEquals("myrecentsite8", $recentSites[2]->getSiteKey());
        $this->assertEquals("myrecentsite7", $recentSites[3]->getSiteKey());
        $this->assertEquals("myrecentsite6", $recentSites[4]->getSiteKey());
        $this->assertEquals("myrecentsite5", $recentSites[5]->getSiteKey());
        $this->assertEquals("myrecentsite4", $recentSites[6]->getSiteKey());
        $this->assertEquals("myrecentsite3", $recentSites[7]->getSiteKey());
        $this->assertEquals("myrecentsite2", $recentSites[8]->getSiteKey());
        $this->assertEquals("myrecentsite1", $recentSites[9]->getSiteKey());


    }


    public function testActivateSiteUsesActivationManagerToActivateSite() {


        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $emailService = $mockObjectProvider->getMockInstance(EmailService::class);
        $activationManager = $mockObjectProvider->getMockInstance(SiteActivationManager::class);

        $siteService = new SiteService(Container::instance()->get(Validator::class), $activationManager, Container::instance()->get(QueuedTaskService::class), $emailService, Container::instance()->get(SecurityService::class),
            Container::instance()->get(ScopeManager::class), Container::instance()->get(SiteStorageManager::class));

        $site = $siteService->createSite(new SiteDescriptor("My Little Pony"), 1);

        // Activate the site
        $siteService->activateSite($site->getSiteId());

        // Grab the site for confirmation of stuff
        $site = Site::fetch($site->getSiteId());

        $this->assertTrue($activationManager->methodWasCalled("activateSite", [$site]));

        // Check for active status
        $this->assertEquals(Site::STATUS_PENDING, $site->getStatus());


    }


    public function testIfCheckActivationisSuccessfulSiteIsActivatedAndEmailSent() {

        $site = $this->siteService->getSiteByKey("markrobertshaw");
        $this->assertEquals(Site::STATUS_PENDING, $site->getStatus());

        // Check activation
        $this->siteService->checkForSiteActivation($site->getSiteId());

        $site = $this->siteService->getSiteByKey("markrobertshaw");
        $this->assertEquals(Site::STATUS_ACTIVE, $site->getStatus());

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new AccountTemplatedEmail("1", "activated", ["site" => $site])
        ]));


    }


    public function testIfCheckActivationIsUnsuccessfulNewSiteCheckIsScheduledIn5MinsProvidedCheckNumberLessThan25() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $site = $this->siteService->getSiteByKey("samdavisdotcom");

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        $this->siteService->checkForSiteActivation($site->getSiteId(), 5);


        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("check-activation-static", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals($site->getSiteId(), $queuedTasks[0]->getConfiguration()["siteId"]);
        $this->assertEquals(6, $queuedTasks[0]->getConfiguration()["checkNumber"]);

        $expectedStartTime = new \DateTime();
        $expectedStartTime->add(new \DateInterval("PT300S"));

        $this->assertEquals($expectedStartTime->format("d/m/Y H:i:s"), $queuedTasks[0]->getStartTime()->format("d/m/Y H:i:s"));


        // Now try final check

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        $this->siteService->checkForSiteActivation($site->getSiteId(), 23);


        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("check-activation-static", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals($site->getSiteId(), $queuedTasks[0]->getConfiguration()["siteId"]);
        $this->assertEquals(24, $queuedTasks[0]->getConfiguration()["checkNumber"]);

        $expectedStartTime = new \DateTime();
        $expectedStartTime->add(new \DateInterval("PT300S"));

        $this->assertEquals($expectedStartTime->format("d/m/Y H:i:s"), $queuedTasks[0]->getStartTime()->format("d/m/Y H:i:s"));


    }


    public function testIfCheckActivationIsUnsuccessful25TimesItIsAbortedAndEmailSentToSuperuser() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $site = $this->siteService->getSiteByKey("samdavisdotcom");

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        $this->siteService->checkForSiteActivation($site->getSiteId(), 24);

        // Check no new task has been created.
        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals(0, sizeof($queuedTasks));


        // Check super user activation failure sent.
        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new SuperUserTemplatedEmail("superuser/activation-failure", ["site" => $site])
        ]));


    }


    public function testCanGetSiteSettings() {

        $site = $this->siteService->createSite(new SiteDescriptor("Test Settings", "testsettings"));

        // Check initial values
        $settings = $this->siteService->getSiteSettings("testsettings");
        $this->assertTrue($settings instanceof SiteSettings);
        $this->assertEquals("", $settings->getPublishDirectory());
        $this->assertEquals("index.html", $settings->getIndexPage());
        $this->assertEquals("404.html", $settings->getNotFoundPage());

        // Now set some values and confirm.
        $site->setConfig(new SiteConfig("public", "myindex.html", "notfound.html"));
        $site->setSiteDomains([
            new SiteDomain("mydomain.hello.com")
        ]);

        $this->siteService->saveSite($site);

        $settings = $this->siteService->getSiteSettings("testsettings");
        $this->assertTrue($settings instanceof SiteSettings);
        $this->assertEquals("public", $settings->getPublishDirectory());
        $this->assertEquals("myindex.html", $settings->getIndexPage());
        $this->assertEquals("notfound.html", $settings->getNotFoundPage());


    }


    public function testCanUpdateSiteSettingsAndQueuedTaskIsCreated() {

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        // Change each setting at at time and confirm changes.
        $this->siteService->updateSiteSettings("testsettings", new SiteSettings(
            "myindex.html", "notfound.html", "dist"));


        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-settings", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "previewBuild" => true], $queuedTasks[0]->getConfiguration());

        $this->assertEquals(new SiteSettings("myindex.html", "notfound.html", "dist"),
            $this->siteService->getSiteSettings("testsettings"));


        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        $this->siteService->updateSiteSettings("testsettings", new SiteSettings(
            "myindex.html", "404.html", "dist"));


        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-settings", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "storageUpdate" => true], $queuedTasks[0]->getConfiguration());

        $this->assertEquals(new SiteSettings("myindex.html", "404.html", "dist"),
            $this->siteService->getSiteSettings("testsettings"));


        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        $this->siteService->updateSiteSettings("testsettings", new SiteSettings(
            "index.html", "404.html", "dist"));

        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-settings", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "storageUpdate" => true], $queuedTasks[0]->getConfiguration());


        $this->assertEquals(new SiteSettings("index.html", "404.html", "dist"),
            $this->siteService->getSiteSettings("testsettings"));


        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        $this->siteService->updateSiteSettings("testsettings", new SiteSettings(
            "index.html", "404.html", "dist"));

        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-settings", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2], $queuedTasks[0]->getConfiguration());


        $this->assertEquals(new SiteSettings("index.html", "404.html", "dist"),
            $this->siteService->getSiteSettings("testsettings"));


        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");


        // Now do an update all run.
        $this->siteService->updateSiteSettings("testsettings", new SiteSettings(
            "index2.html", "broken.html", "public"));

        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-settings", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "storageUpdate" => true, "previewBuild" => true], $queuedTasks[0]->getConfiguration());

        $this->assertEquals(new SiteSettings("index2.html", "broken.html", "public"),
            $this->siteService->getSiteSettings("testsettings"));


    }


    public function testSettingOrUnsettingMaintenanceModeUpdatesDatabaseAndCreatesTask() {

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        // Get the site
        $site = $this->siteService->getSiteByKey("testsettings");
        $this->assertFalse($site->isMaintenanceMode());

        $this->siteService->updateMaintenanceMode("testsettings", true);

        $site = $this->siteService->getSiteByKey("testsettings");
        $this->assertTrue($site->isMaintenanceMode());

        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-maintenance", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "maintenance" => true], $queuedTasks[0]->getConfiguration());


        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        $this->siteService->updateMaintenanceMode("testsettings", false);

        $site = $this->siteService->getSiteByKey("testsettings");
        $this->assertFalse($site->isMaintenanceMode());

        $queuedTasks = $this->queuedTaskService->listQueuedTasks(Configuration::readParameter("queue.name"));
        $this->assertEquals("update-site-maintenance", $queuedTasks[0]->getTaskIdentifier());
        $this->assertEquals(["siteKey" => "testsettings", "initiatingUserId" => 2, "maintenance" => false], $queuedTasks[0]->getConfiguration());

    }

}
