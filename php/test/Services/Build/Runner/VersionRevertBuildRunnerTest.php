<?php

namespace Kinihost\Services\Build\Runner;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Services\Storage\StorageRoot;
use Kinihost\Services\Storage\VersionedStorageRoot;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Content\ContentService;
use Kinihost\Services\Content\DeploymentProcessors\ContentDeploymentProcessor;
use Kinihost\Services\Content\DeploymentProcessors\MetaDataDeploymentProcessor;
use Kinihost\Services\Content\EntityDefinitionService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\Services\Source\SourceService;
use Kinihost\TestBase;

include_once "autoloader.php";

class VersionRevertBuildRunnerTest extends TestBase {


    /**
     * @var CurrentBuildRunner
     */
    private $buildRunner;

    /**
     * @var MockObject
     */
    private $siteStorageManager;


    /**
     * @var MockObject
     */
    private $contentStorageRoot;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->siteStorageManager = $mockObjectProvider->getMockInstance(SiteStorageManager::class);
        $this->contentStorageRoot = $mockObjectProvider->getMockInstance(VersionedStorageRoot::class);
        $this->buildRunner = new VersionRevertBuildRunner($this->siteStorageManager);
    }


    public function testBuildRevertsVersionAndThenGetsLatestChangesAndAppliesThemToTargetAsExpected() {

        $site = new Site("My New Site", "mynewsite");
        $build = new Build($site, Build::TYPE_CURRENT, null, null, ["targetVersion" => 3]);

        $changes = [new ChangedObject("test1.txt", ChangedObject::CHANGE_TYPE_UPDATE)];

        $processingRoot = MockObjectProvider::instance()->getMockInstance(StorageRoot::class);

        // Return our mock storage root if get preview root called.
        $this->siteStorageManager->returnValue("getContentRoot", $this->contentStorageRoot, [$site]);
        $this->siteStorageManager->returnValue("getProcessingRoot", $processingRoot, [$site]);


        $this->buildRunner->runBuild($build, $site);

        $this->assertTrue($this->siteStorageManager->methodWasCalled("getContentRoot", [$site]));
        $this->assertTrue($this->contentStorageRoot->methodWasCalled("revertToPreviousVersion", [3]));

    }


}
