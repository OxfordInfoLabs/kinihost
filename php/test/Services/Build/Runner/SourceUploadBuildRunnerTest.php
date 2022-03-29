<?php

namespace Kinihost\Services\Build\Runner;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinihost\Services\Storage\StorageRoot;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Exception\EntityValidationException;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteConfig;
use Kinihost\Services\Content\ContentService;
use Kinihost\Services\Content\DeploymentProcessors\BootstrapDataProcessor;
use Kinihost\Services\Content\DeploymentProcessors\ContentDeploymentProcessor;
use Kinihost\Services\Content\DeploymentProcessors\MetaDataDeploymentProcessor;
use Kinihost\Services\Content\EntityDefinitionService;
use Kinihost\Services\Site\SiteDataUpdateManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\Services\Source\DeploymentProcessors\CachedDataDeploymentProcessor;
use Kinihost\Services\Site\SiteSourceService;
use Kinihost\TestBase;

include_once "autoloader.php";

class SourceUploadBuildRunnerTest extends TestBase {


    /**
     * @var SourceUploadBuildRunner
     */
    private $buildRunner;


    /**
     * @var MockObject
     */
    private $siteService;

    /**
     * @var MockObject
     */
    private $siteSourceService;

    /**
     * @var MockObject
     */
    private $siteStorageManager;


    /**
     * @var MockObject
     */
    private $storageRoot;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->siteSourceService = $mockObjectProvider->getMockInstance(SiteSourceService::class);
        $this->siteStorageManager = $mockObjectProvider->getMockInstance(SiteStorageManager::class);
        $this->storageRoot = $mockObjectProvider->getMockInstance(StorageRoot::class);
        $this->siteService = $mockObjectProvider->getMockInstance(SiteService::class);

        $this->buildRunner = new SourceUploadBuildRunner($this->siteService, $this->siteSourceService, Container::instance()->get(ObjectBinder::class));
    }


    public function testBuildGetsLatestChangesAndAppliesThemToTargetAsExpected() {

        $site = new Site("My New Site", "mynewsite");
        $build = new Build($site, Build::TYPE_SOURCE_UPLOAD, Build::STATUS_RUNNING, null, ["changedObjects" => [
            ["objectKey" => "test1.txt", "changeType" => ChangedObject::CHANGE_TYPE_UPDATE]
        ]]);


        $changes = [new ChangedObject("test1.txt", ChangedObject::CHANGE_TYPE_UPDATE)];

        $processingRoot = MockObjectProvider::instance()->getMockInstance(StorageRoot::class);

        // Return our mock storage root if get preview root called.
        $this->siteStorageManager->returnValue("getPreviewRoot", $this->storageRoot, [$site]);
        $this->siteStorageManager->returnValue("getProcessingRoot", $processingRoot, [$site]);
        $this->siteSourceService->returnValue("getCurrentDeploymentChangedFiles", $changes, [$site]);


        $this->buildRunner->runBuild($build, $site);

        // Check that the apply uploaded source method is called correctly.
        $this->assertTrue($this->siteSourceService->methodWasCalled("applyUploadedSource", [null, $changes, $site]));

    }


    public function testBuildReadsSiteConfigFromContentDirectoryAndAppliesItToSite() {

        $site = new Site("My New Site", "mynewsite");
        $build = new Build($site, Build::TYPE_SOURCE_UPLOAD, Build::STATUS_RUNNING, null, ["changedObjects" => [
            ["objectKey" => "test1.txt", "changeType" => ChangedObject::CHANGE_TYPE_UPDATE]
        ]]);

        $expectedSiteConfig = new SiteConfig("public", "index.html", "notfound.html");

        // Programme the return value for current site config
        $this->siteSourceService->returnValue("getCurrentSiteConfig", $expectedSiteConfig, ["mynewsite"]);

        $this->buildRunner->runBuild($build, $site);

        $this->assertTrue($this->siteService->methodWasCalled("updateSiteSettings", [
            "mynewsite", $expectedSiteConfig
        ]));

    }


}
