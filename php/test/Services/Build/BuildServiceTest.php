<?php

namespace Kinihost\Services\Build;

use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinihost\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Exception\ConcurrentBuildException;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Build\Runner\CurrentBuildRunner;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteSourceService;
use Kinihost\ValueObjects\Site\SiteDescriptor;

include_once "autoloader.php";

class BuildServiceTest extends TestBase {

    /**
     * @var BuildService
     */
    private $buildService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var MockObject
     */
    private $queuedTaskService;


    /**
     * @var SiteService
     */
    private $siteService;


    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * @var MockObject
     */
    private $emailService;


    // Set up
    public function setUp(): void {

        $this->authenticationService = Container::instance()->get(AuthenticationService::class);

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->queuedTaskService = $mockObjectProvider->getMockInstance(QueuedTaskService::class);
        $this->siteService = Container::instance()->get(SiteService::class);
        $this->securityService = Container::instance()->get(SecurityService::class);
        $this->emailService = $mockObjectProvider->getMockInstance(EmailService::class);

        $sourceService = Container::instance()->get(SiteSourceService::class);
        $sourceService->getBlankSiteRoot()->create();

        $this->buildService = new BuildService($this->siteService, $this->queuedTaskService, $this->securityService, $this->emailService);

    }

    public function testCanCreateNewBuildForSiteWithDefaultValues() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $build = $this->buildService->createBuild("woollenmill", Build::TYPE_CURRENT);
        $this->assertTrue(is_numeric($build->getId()));

        $this->assertEquals(Build::TYPE_CURRENT, $build->getBuildType());
        $this->assertEquals(2, $build->getSiteId());
        $this->assertEquals(1, $build->getAccountId());
        $this->assertEquals(2, $build->getInitiatingUserId());
        $this->assertNotNull($build->getCreatedDate());
        $this->assertNotNull($build->getQueuedDate());
        $this->assertEquals(Build::STATUS_QUEUED, $build->getStatus());

        // Check build number incremented for site and added to build
        $this->assertEquals(1, $build->getSiteBuildNumber());
        $site = $this->siteService->getSiteByKey("woollenmill");
        $this->assertEquals(1, $site->getLastBuildNumber());

    }


    public function testCanCreateNewBuildWithExtraParams() {

        $build = $this->buildService->createBuild("pingu", Build::TYPE_SOURCE_UPLOAD,  Build::STATUS_PENDING, ["changedFiles" => 2]);
        $this->assertTrue(is_numeric($build->getId()));

        $this->assertEquals(Build::TYPE_SOURCE_UPLOAD, $build->getBuildType());
        $this->assertEquals(3, $build->getSiteId());
        $this->assertNotNull($build->getCreatedDate());
        $this->assertNull($build->getQueuedDate());
        $this->assertEquals(Build::STATUS_PENDING, $build->getStatus());
        $this->assertEquals(["changedFiles" => 2], $build->getData());

        // Check build number incremented for site and added to build
        $this->assertEquals(1, $build->getSiteBuildNumber());
        $site = $this->siteService->getSiteByKey("pingu");
        $this->assertEquals(1, $site->getLastBuildNumber());


    }


    public function testCanQueuePendingBuildAndQueuedTaskCreated() {

        $build = $this->buildService->createBuild("pingu", Build::TYPE_SOURCE_UPLOAD,  Build::STATUS_PENDING, ["changedFiles" => 2]);

        // Queue the build
        $this->buildService->queueBuild($build->getId());

        $build = $this->buildService->getBuild($build->getId());
        $this->assertEquals(Build::TYPE_SOURCE_UPLOAD, $build->getBuildType());
        $this->assertEquals(3, $build->getSiteId());
        $this->assertNotNull($build->getCreatedDate());
        $this->assertNotNull($build->getQueuedDate());
        $this->assertEquals(Build::STATUS_QUEUED, $build->getStatus());
        $this->assertEquals(["changedFiles" => 2], $build->getData());


        // Check task was queued.
        $this->assertTrue($this->queuedTaskService->methodWasCalled("queueTask",
            ["kinihost-test", "run-site-build", "Run Build: " . $build->getId(), ["buildId" => $build->getId()]]));


    }


    public function testRunBuildThrowsExceptionIfBuildAlreadyStartedForSite() {


        $build = $this->buildService->createBuild("pingu", Build::TYPE_SOURCE_UPLOAD,  Build::STATUS_PENDING, [new ChangedObject("test1.txt", ChangedObject::CHANGE_TYPE_UPDATE)]);

        $build->registerStatusChange(Build::STATUS_RUNNING);

        $build2 = $this->buildService->createBuild("pingu", Build::TYPE_SOURCE_UPLOAD,  Build::STATUS_PENDING, [new ChangedObject("test1.txt", ChangedObject::CHANGE_TYPE_UPDATE)]);

        try {
            $this->buildService->runBuild($build2->getId());
            $this->fail("Should have thrown here");
        } catch (ConcurrentBuildException $e) {
            $this->assertTrue(true);
        }

    }


    public function testIfNoCompetingBuildAndChangedObjectsPresentTheseAreProcessedUsingTheSourceService() {


        AuthenticationHelper::login("james@smartcoasting.org", "password");


        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $mockBuildRunner = $mockObjectProvider->getMockInstance(CurrentBuildRunner::class);
        Container::instance()->set(CurrentBuildRunner::class, $mockBuildRunner);

        $site = $this->siteService->createSite(new SiteDescriptor("Zulu"));
        $this->siteService->activateSite($site->getSiteId());

        $this->assertEquals(null, $site->getLastPreviewed());

        $build = $this->buildService->createBuild("zulu", Build::TYPE_CURRENT,  Build::STATUS_PENDING, ["Hello"]);

        $this->buildService->runBuild($build->getId());

        $this->assertTrue($mockBuildRunner->methodWasCalled("runBuild"));

        $reBuild = Build::fetch($build->getId());
        $this->assertEquals(Build::STATUS_SUCCEEDED, $reBuild->getStatus());
        $this->assertNotNull($reBuild->getStartedDate());
        $this->assertNotNull($reBuild->getCompletedDate());



    }


    public function testEmailSentToInitiatingUserOnceBuildSucceedsOrFails() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $mockBuildRunner = $mockObjectProvider->getMockInstance(CurrentBuildRunner::class);
        Container::instance()->set(CurrentBuildRunner::class, $mockBuildRunner);

        $build = $this->buildService->createBuild("samdavisdotcom", Build::TYPE_CURRENT,  Build::STATUS_PENDING, ["Hello"]);

        $this->buildService->runBuild($build->getId());

        $site = $this->siteService->getSiteByKey("samdavisdotcom");
        $build = $this->buildService->getBuild($build->getId());

        $this->assertTrue($mockBuildRunner->methodWasCalled("runBuild"));

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "build-success", [
                "build" => $build,
                "site" => $site
            ])
        ]));


        $mockBuildRunner->throwException("runBuild", new \Exception("Bad Build"));

        $build = $this->buildService->createBuild("samdavisdotcom", Build::TYPE_CURRENT,  Build::STATUS_PENDING, ["Hello"]);

        $this->buildService->runBuild($build->getId());

        $site = $this->siteService->getSiteByKey("samdavisdotcom");
        $build = $this->buildService->getBuild($build->getId());

        $this->assertEquals(Build::STATUS_FAILED, $build->getStatus());
        $this->assertEquals("Bad Build", $build->getFailureMessage());

        $this->assertTrue($mockBuildRunner->methodWasCalled("runBuild"));

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "build-failure", [
                "build" => $build,
                "site" => $site
            ])
        ]));


    }


}
