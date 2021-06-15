<?php

namespace Kinihost\Services\QueuedTasks;

use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Build\BuildService;
use Kinihost\Services\Site\SiteDNSManager;
use Kinihost\Services\Site\SiteRoutingManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\TestBase;

include_once "autoloader.php";

class UpdateSiteSettingsTaskTest extends TestBase {

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
    private $buildService;


    /**
     * @var MockObject
     */
    private $emailService;

    /**
     * @var UpdateSiteSettingsTask
     */
    private $updateSettingsTask;


    /**
     * @var Site
     */
    private $exampleSite;

    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->siteService = $mockObjectProvider->getMockInstance(SiteService::class);
        $this->siteRoutingManager = $mockObjectProvider->getMockInstance(SiteRoutingManager::class);
        $this->siteStorageManager = $mockObjectProvider->getMockInstance(SiteStorageManager::class);
        $this->siteDNSManager = $mockObjectProvider->getMockInstance(SiteDNSManager::class);
        $this->buildService = $mockObjectProvider->getMockInstance(BuildService::class);
        $this->emailService = $mockObjectProvider->getMockInstance(EmailService::class);

        $this->updateSettingsTask = new UpdateSiteSettingsTask($this->siteService, $this->siteRoutingManager, $this->siteStorageManager, $this->siteDNSManager,$this->buildService, $this->emailService);

        $this->exampleSite = new Site("Example Site", "example");
        $this->siteService->returnValue("getSiteByKey", $this->exampleSite, ["example"]);

    }


    public function testIfPreviewBuildParamSetNewPreviewBuildIsCreated() {

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "previewBuild" => true
        ]);

        $this->assertTrue($this->siteService->methodWasCalled("getSiteByKey", ["example"]));
        $this->assertTrue($this->buildService->methodWasCalled("createBuild", ["example", Build::TYPE_CURRENT]));
        $this->assertFalse($this->siteStorageManager->methodWasCalled("updateStorage"));
        $this->assertFalse($this->siteRoutingManager->methodWasCalled("updateRouting"));

    }


    public function testIfStorageUpdateSetCheckThisIsCalledCorrectly() {

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true
        ]);


        $this->assertTrue($this->siteService->methodWasCalled("getSiteByKey", ["example"]));
        $this->assertFalse($this->buildService->methodWasCalled("createBuild"));
        $this->assertTrue($this->siteStorageManager->methodWasCalled("updateStorage", [$this->exampleSite]));
        $this->assertFalse($this->siteRoutingManager->methodWasCalled("updateRouting"));


    }


    public function testIfRoutingUpdateSetCheckThisIsCalledCorrectly() {

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "routingUpdate" => true
        ]);


        $this->assertTrue($this->siteService->methodWasCalled("getSiteByKey", ["example"]));
        $this->assertFalse($this->buildService->methodWasCalled("createBuild"));
        $this->assertFalse($this->siteStorageManager->methodWasCalled("updateStorage"));
        $this->assertTrue($this->siteRoutingManager->methodWasCalled("updateRouting", [$this->exampleSite]));


    }


    public function testIfMultipleItemsSetCheckTheseAreAllCalled() {

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true,
            "routingUpdate" => true,
            "previewBuild" => true
        ]);

        $this->assertTrue($this->siteService->methodWasCalled("getSiteByKey", ["example"]));
        $this->assertTrue($this->buildService->methodWasCalled("createBuild", ["example", Build::TYPE_CURRENT]));
        $this->assertTrue($this->siteStorageManager->methodWasCalled("updateStorage", [$this->exampleSite]));
        $this->assertTrue($this->siteRoutingManager->methodWasCalled("updateRouting", [$this->exampleSite]));

    }


    public function testSuccessEmailSentToInitiatingUserIfSetAndAllSucceeds() {

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true,
            "routingUpdate" => true,
            "previewBuild" => true
        ]);

        $this->assertFalse($this->emailService->methodWasCalled("send"));


        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true,
            "routingUpdate" => true,
            "previewBuild" => true,
            "initiatingUserId" => 2
        ]);

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "settings-updated", [
                "siteName" => "Example Site"
            ])
        ]));

    }


    public function testFailureEmailSentToInitiatingUserIfSetAndFailureOccurs() {


        $this->siteStorageManager->throwException("updateStorage", new \Exception("Random Failure"));

        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true,
            "routingUpdate" => true,
            "previewBuild" => true
        ]);

        $this->assertFalse($this->emailService->methodWasCalled("send"));


        $this->updateSettingsTask->run([
            "siteKey" => "example",
            "storageUpdate" => true,
            "routingUpdate" => true,
            "previewBuild" => true,
            "initiatingUserId" => 2
        ]);

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            new UserTemplatedEmail(2, "settings-failed", [
                "siteName" => "Example Site",
                "failureMessage" => "Random Failure"
            ])
        ]));

    }




}
