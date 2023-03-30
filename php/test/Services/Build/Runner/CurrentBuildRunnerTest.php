<?php


namespace Kinihost\Services\Build\Runner;


use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Site\SiteSourceService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\Services\Storage\StorageRoot;
use Kinihost\TestBase;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Util\ObjectArrayUtils;

include_once "autoloader.php";

class CurrentBuildRunnerTest extends TestBase {


    /**
     * @var CurrentBuildRunner
     */
    private $currentBuildRunner;


    /**
     * @var MockObject
     */
    private $siteSourceService;


    /**
     * @var MockObject
     */
    private $siteStorageManager;


    public function setUp(): void {
        $this->siteSourceService = MockObjectProvider::instance()->getMockInstance(SiteSourceService::class);
        $this->siteStorageManager = MockObjectProvider::instance()->getMockInstance(SiteStorageManager::class);
        $this->currentBuildRunner = new CurrentBuildRunner($this->siteSourceService, $this->siteStorageManager);
    }


    public function testCanRunPreviewBuild() {

        $site = new Site("Test Site");
        $build = new Build($site, Build::TYPE_PREVIEW);

        // Programme preview root
        $previewRoot = MockObjectProvider::instance()->getMockInstance(StorageRoot::class);
        $this->siteStorageManager->returnValue("getPreviewRoot", $previewRoot);

        $changes = [
            new ChangedObject("test1", "ADD"),
            new ChangedObject("test2", "DELETE")
        ];

        $indexedChanges = ObjectArrayUtils::indexArrayOfObjectsByMember("objectKey", $changes);

        $this->siteSourceService->returnValue("getCurrentDeploymentChangedFiles", $changes, [$site]);


        $this->currentBuildRunner->runBuild($build, $site);


        // Check that the preview was replaced
        $this->assertTrue($previewRoot->methodWasCalled("replaceAll", [$indexedChanges]));


    }


    public function testCanRunPublishBuild() {

        $site = new Site("Test Site");
        $build = new Build($site, Build::TYPE_PUBLISH);

        // Programme preview root
        $productionRoot = MockObjectProvider::instance()->getMockInstance(StorageRoot::class);
        $this->siteStorageManager->returnValue("getProductionRoot", $productionRoot);

        $changes = [
            new ChangedObject("test1", "ADD"),
            new ChangedObject("test2", "DELETE")
        ];

        $indexedChanges = ObjectArrayUtils::indexArrayOfObjectsByMember("objectKey", $changes);

        $this->siteSourceService->returnValue("getCurrentDeploymentChangedFiles", $changes, [$site]);


        $this->currentBuildRunner->runBuild($build, $site);


        // Check that the preview was replaced
        $this->assertTrue($productionRoot->methodWasCalled("replaceAll", [$indexedChanges]));


    }


}
