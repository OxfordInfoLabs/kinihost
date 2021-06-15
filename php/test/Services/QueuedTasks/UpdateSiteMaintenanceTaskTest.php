<?php


namespace Kinihost\Services\QueuedTasks;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Build\BuildService;
use Kinihost\Services\Site\SiteDNSManager;
use Kinihost\Services\Site\SiteRoutingManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\TestBase;

include_once "autoloader.php";

class UpdateSiteMaintenanceTaskTest extends TestBase {

    /**
     * @var MockObject
     */
    private $siteService;

    /**
     * @var MockObject
     */
    private $siteRoutingManager;


    /**
     * @var MockObject
     */
    private $siteStorageManager;


    /**
     * @var MockObject
     */
    private $siteDNSManager;


    /**
     * @var MockObject
     */
    private $emailService;


    /**
     * @var Site
     */
    private $exampleSite;


    /**
     * @var MaintenanceModeTask
     */
    private $maintenanceModeTask;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->siteService = $mockObjectProvider->getMockInstance(SiteService::class);
        $this->siteRoutingManager = $mockObjectProvider->getMockInstance(SiteRoutingManager::class);
        $this->siteStorageManager = $mockObjectProvider->getMockInstance(SiteStorageManager::class);
        $this->siteDNSManager = $mockObjectProvider->getMockInstance(SiteDNSManager::class);
        $this->emailService = $mockObjectProvider->getMockInstance(EmailService::class);

        $this->maintenanceModeTask = new UpdateSiteMaintenanceTask($this->siteService, $this->siteRoutingManager, $this->siteStorageManager, $this->siteDNSManager, $this->emailService);

        $this->exampleSite = new Site("Example Site", "example");
        $this->siteService->returnValue("getSiteByKey", $this->exampleSite, ["example"]);

        $authenticationService = Container::instance()->get(AuthenticationService::class);
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

    }


    public function testIfMaintenanceModeSetToTrueAndSucceedsDNSManagerIsCalledAndEmailSent() {

        $this->maintenanceModeTask->run([
            "siteKey" => "example",
            "initiatingUserId" => 2,
            "maintenance" => true
        ]);


        $this->assertTrue($this->siteDNSManager->methodWasCalled("activateMaintenanceDNS", [$this->exampleSite]));

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "maintenance-activated", [
                "siteName" => "Example Site"
            ])
        ]));

    }


    public function testIfMaintenanceModeSetToFalseAndSucceedsRoutingAndDNSManagersAreCalledAndEmailSent() {


        // Program a return value for update of routing
        $testRouting = new Routing("pingu", [], []);
        $this->siteRoutingManager->returnValue("updateRouting", $testRouting, [$this->exampleSite]);


        $this->maintenanceModeTask->run([
            "siteKey" => "example",
            "initiatingUserId" => 2,
            "maintenance" => false
        ]);


        // Check routing was updated
        $this->assertTrue($this->siteRoutingManager->methodWasCalled("updateRouting", [$this->exampleSite]));

        // Check create service DNS for site was called.
        $this->assertTrue($this->siteDNSManager->methodWasCalled("createServiceDNSForSite", [$this->exampleSite, $testRouting]));

        // Check maintenance deactivated email was sent.
        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "maintenance-deactivated", [
                "siteName" => "Example Site"
            ])
        ]));

    }


    public function testIfMaintenanceModeUpdateFailsEmailIsSent() {

        // Program a return value for update of routing
        $this->siteRoutingManager->throwException("updateRouting", new \Exception("Test exception"), [$this->exampleSite]);

        $this->maintenanceModeTask->run([
            "siteKey" => "example",
            "initiatingUserId" => 2,
            "maintenance" => false
        ]);

        // Check maintenance deactivated email was sent.
        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "maintenance-failed", [
                "siteName" => "Example Site",
                "failureMessage" => "Test exception"
            ])
        ]));
    }


}
