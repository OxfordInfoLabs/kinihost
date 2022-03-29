<?php

namespace Kinihost\Services;

use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinihost\Services\Site\SiteSourceService;
use Kinihost\ValueObjects\Build\SourceUploadBuild;
use Kinihost\ValueObjects\Site\SiteDescriptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinihost\Services\Storage\VersionedStorageRoot;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteConfig;
use Kinihost\Services\Build\BuildService;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\TestBase;

include_once "autoloader.php";

class SourceServiceTest extends TestBase {


    /**
     * @var SiteSourceService
     */
    private $sourceService;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var SiteService
     */
    private $siteService;


    /**
     * @var QueuedTaskService
     */
    private $queueService;

    /**
     * @var SiteStorageManager
     */
    private $siteStorageManager;


    /**
     * @var BuildService
     */
    private $buildService;

    // Set up function
    public function setUp(): void {
        $this->sourceService = Container::instance()->get(SiteSourceService::class);
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->siteService = Container::instance()->get(SiteService::class);
        $this->siteStorageManager = Container::instance()->get(SiteStorageManager::class);
        $this->queueService = Container::instance()->get(QueuedTaskService::class);
        $this->buildService = Container::instance()->get(BuildService::class);

        $this->sourceService->getBlankSiteRoot()->create();

    }


    public function testCanGetCurrentSourceObjectFootprintsForSite() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestSourceSite"));
        $this->siteService->activateSite($site->getSiteId());

        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        $contentRoot->saveObject("source/drama.txt", "drama");
        $contentRoot->saveObject("source/english.txt", "And English Play");

        $contentRoot->saveObject("data/other.txt", "other");
        $contentRoot->saveObject("data/plans.txt", "Plans");


        $this->assertEquals($contentRoot->getObjectFootprints("source"), $this->sourceService->getCurrentSourceObjectFootprints("testsourcesite"));


    }


    public function testCanGetCurrentDeploymentSourceObjectsAsLocalFileChangedObjects() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestSourceSite"));
        $this->siteService->activateSite($site->getSiteId());

        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        $contentRoot->saveObject("source/drama.txt", "drama");
        $contentRoot->saveObject("source/english.txt", "And English Play");

        $contentRoot->saveObject("data/other.txt", "other");
        $contentRoot->saveObject("data/plans.txt", "Plans");


        $this->assertEquals([
            "drama.txt" => new ChangedObject("drama.txt", ChangedObject::CHANGE_TYPE_UPDATE, null, "FileStorage/testsourcesite2-content.kinihost.test/current/source/drama.txt", md5("drama")),
            "english.txt" => new ChangedObject("english.txt", ChangedObject::CHANGE_TYPE_UPDATE, null, "FileStorage/testsourcesite2-content.kinihost.test/current/source/english.txt", md5("And English Play")),
        ], $this->sourceService->getCurrentDeploymentChangedFiles($site));

    }


    public function testCanCreateSourceUploadBuildAndThisCreatesNewBuildAndGeneratesUploadUrls() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestSourceUploadSite"));
        $this->siteService->activateSite($site->getSiteId());

        $changedObjects = [
            new ChangedObject("test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "65363465"),
            new ChangedObject("my/little/test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "97808080"),
            new ChangedObject("test/test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "67575477"),
            new ChangedObject("test/test3.html", ChangedObject::CHANGE_TYPE_DELETE, null, null, null)
        ];

        $response = $this->sourceService->createSourceUploadBuild($site->getSiteKey(), $changedObjects);

        $this->assertTrue($response instanceof SourceUploadBuild);
        $this->assertNotNull($response->getBuildId());

        $uploadUrls = $response->getUploadUrls();
        $this->assertEquals(3, sizeof($uploadUrls));
        $this->assertEquals("/upload/testsourceuploadsite-content.kinihost.test/upload/" . $response->getBuildId() . "/test.html", $uploadUrls["test.html"]);
        $this->assertEquals("/upload/testsourceuploadsite-content.kinihost.test/upload/" . $response->getBuildId() . "/my/little/test.html", $uploadUrls["my/little/test.html"]);
        $this->assertEquals("/upload/testsourceuploadsite-content.kinihost.test/upload/" . $response->getBuildId() . "/test/test.html", $uploadUrls["test/test.html"]);


        // Check the build is stored correctly
        $build = Build::fetch($response->getBuildId());

        $this->assertEquals(Build::STATUS_PENDING, $build->getStatus());
        $this->assertEquals($site->getSiteId(), $build->getSiteId());
        $buildChangedObjects = $build->getData();
        $this->assertEquals(4, sizeof($buildChangedObjects["changedObjects"]));


        $this->assertNotNull($build->getCreatedDate());
        $this->assertNull($build->getQueuedDate());
        $this->assertNull($build->getStartedDate());
        $this->assertNull($build->getCompletedDate());


    }


    public function testCanApplySourceChangesFromUploadedBuild() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $site = $this->siteService->getSiteByKey("testsourceuploadsite");

        passthru("rm -rf FileStorage/testsourceuploadsite-content.kinihost.test/*");

        // Upload to the upload root for this site.
        $uploadRoot = $this->siteStorageManager->getUploadRoot($site);

        $uploadRoot->saveObject("1/test1.html", "BLAAH BLAAH");
        $uploadRoot->saveObject("1/test2.html", "BLAAH BLAAH 2");
        $uploadRoot->saveObject("1/sub/test3.html", "BLAAH BLAAH 3");

        $changes = [
            new ChangedObject("test1.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "FFFFFF"),
            new ChangedObject("test2.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "AAAAAA"),
            new ChangedObject("sub/test3.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "BBBBBB"),
        ];

        $this->sourceService->applyUploadedSource(1, $changes, $site);

        $footprints = $this->siteStorageManager->getContentRoot($site)->getObjectFootprints();
        $this->assertEquals(3, sizeof($footprints));
        $this->assertEquals("FFFFFF", $footprints["source/test1.html"]);
        $this->assertEquals("AAAAAA", $footprints["source/test2.html"]);
        $this->assertEquals("BBBBBB", $footprints["source/sub/test3.html"]);

        // Now confirm that the main content has been updated
        $this->assertEquals("BLAAH BLAAH", file_get_contents("FileStorage/testsourceuploadsite-content.kinihost.test/current/source/test1.html"));
        $this->assertEquals("BLAAH BLAAH 2", file_get_contents("FileStorage/testsourceuploadsite-content.kinihost.test/current/source/test2.html"));
        $this->assertEquals("BLAAH BLAAH 3", file_get_contents("FileStorage/testsourceuploadsite-content.kinihost.test/current/source/sub/test3.html"));


        // Check that the upload folder is deleted afterwards.
        $this->assertFalse(file_exists("FileStorage/testsourceuploadsite-content.kinihost.test/upload/1"));


    }


    public function testCanInstallBlankContentInSite() {

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        if (!file_exists("FileStorage/themes.kinihost.test/global/default/current"))
            mkdir("FileStorage/themes.kinihost.test/global/default/current", 0777, true);

        $themeRoot = new VersionedStorageRoot("file", "themes.kinihost.test", "global/default");
        $themeRoot->saveObject("theme.html", "TEST THEME FILE 1");
        $themeRoot->saveObject("theme2.html", "TEST THEME FILE 2");


        $site = $this->siteService->getSiteByKey("testsourceuploadsite");

        // Install blank content
        $this->sourceService->installBlankContent($site);

        $root = $this->siteStorageManager->getContentRoot($site);
        $this->assertEquals(2, sizeof($root->getObjectFootprints()));
        $this->assertEquals(md5("TEST THEME FILE 1"), $root->getObjectFootprints()["source/theme.html"]);
        $this->assertEquals(md5("TEST THEME FILE 2"), $root->getObjectFootprints()["source/theme2.html"]);

        $this->assertEquals("TEST THEME FILE 1", file_get_contents("FileStorage/testsourceuploadsite-content.kinihost.test/current/source/theme.html"));
        $this->assertEquals("TEST THEME FILE 2", file_get_contents("FileStorage/testsourceuploadsite-content.kinihost.test/current/source/theme2.html"));

        $builds = Build::filter("ORDER BY id DESC");
        $this->assertEquals($site->getSiteId(), $builds[0]->getSiteId());


        $queuedItems = $this->queueService->listQueuedTasks("kinihost-test");
        $this->assertEquals("run-site-build", $queuedItems[0]->getTaskIdentifier());


    }


    public function testCanInitialiseProductionContent() {

        Container::instance()->get(DatabaseConnection::class)->query("DELETE FROM ka_queue");

        if (!file_exists("FileStorage/themes.kinihost.test/global/default/current"))
            mkdir("FileStorage/themes.kinihost.test/global/default/current", 0777, true);

        $themeRoot = new VersionedStorageRoot("file", "themes.kinihost.test", "global/default");
        $themeRoot->saveObject("theme.html", "TEST THEME FILE 1");
        $themeRoot->saveObject("theme2.html", "TEST THEME FILE 2");


        $site = $this->siteService->getSiteByKey("testsourceuploadsite");

        // Initialise production content.
        $this->sourceService->initialiseProductionContent($site);


        // Check it has now been updated.
        $productionRoot = $this->siteStorageManager->getProductionRoot($site);

        $this->assertEquals(2, sizeof($productionRoot->getObjectFootprints()));
        $this->assertEquals(md5("TEST THEME FILE 1"), $productionRoot->getObjectFootprints()["theme.html"]);
        $this->assertEquals(md5("TEST THEME FILE 2"), $productionRoot->getObjectFootprints()["theme2.html"]);


    }


    public function testCanCreateSourceDownloadURLsForSetOfSourceFiles() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestSourceDownloadSite"));
        $this->siteService->activateSite($site->getSiteId());

        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        $changedObjects = [
            new ChangedObject("source/test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "65363465"),
            new ChangedObject("source/my/little/test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "97808080"),
            new ChangedObject("source/test/test.html", ChangedObject::CHANGE_TYPE_UPDATE, null, null, "67575477"),
            new ChangedObject("source/test/test3.html", ChangedObject::CHANGE_TYPE_DELETE, null, null, null)
        ];

        $contentRoot->replaceAll($changedObjects);

        $downloadUrls = $this->sourceService->createSourceDownloadURLs($site->getSiteKey(), [
            "test.html",
            "my/little/test.html",
            "test/test.html",
            "test/test3.html"
        ]);

        $this->assertEquals(4, sizeof($downloadUrls));
        $this->assertEquals("/download/testsourcedownloadsi-content.kinihost.test/current/source/test.html", $downloadUrls["test.html"]);
        $this->assertEquals("/download/testsourcedownloadsi-content.kinihost.test/current/source/my/little/test.html", $downloadUrls["my/little/test.html"]);
        $this->assertEquals("/download/testsourcedownloadsi-content.kinihost.test/current/source/test/test3.html", $downloadUrls["test/test3.html"]);
        $this->assertEquals("/download/testsourcedownloadsi-content.kinihost.test/current/source/test/test.html", $downloadUrls["test/test.html"]);


    }


    public function testCanListCurrentSourceForSite() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestSourceListingSite"));
        $this->siteService->activateSite($site->getSiteId());

        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        $changedObjects = [
            new ChangedObject("source/test.html", ChangedObject::CHANGE_TYPE_UPDATE, "Pick and mix", null, "65363465"),
            new ChangedObject("source/my/little/test.html", ChangedObject::CHANGE_TYPE_UPDATE, "New file", null, "97808080"),
            new ChangedObject("source/test/test.html", ChangedObject::CHANGE_TYPE_UPDATE, "Bingo Bongo", null, "67575477"),
            new ChangedObject("source/test/test3.html", ChangedObject::CHANGE_TYPE_UPDATE, "bish bash bosh", null, null)
        ];

        $contentRoot->replaceAll($changedObjects);

        $rootList = $this->sourceService->listCurrentSourceForSite($site->getSiteKey());

        $this->assertEquals(3, sizeof($rootList));
        $this->assertEquals(new StoredObjectSummary("testsourcelistingsit-content.kinihost.test", "source/my",
            "folder", 0, $rootList[0]->getCreatedTime(), $rootList[0]->getLastModifiedTime()), $rootList[0]);

        $this->assertEquals(new StoredObjectSummary("testsourcelistingsit-content.kinihost.test", "source/test",
            "folder", 0, $rootList[1]->getCreatedTime(), $rootList[1]->getLastModifiedTime()), $rootList[1]);

        $this->assertEquals(new StoredObjectSummary("testsourcelistingsit-content.kinihost.test", "source/test.html",
            "text/plain", 12, $rootList[2]->getCreatedTime(), $rootList[2]->getLastModifiedTime()), $rootList[2]);


        $subList = $this->sourceService->listCurrentSourceForSite($site->getSiteKey(), "test");
        $this->assertEquals(2, sizeof($subList));

        $this->assertEquals(new StoredObjectSummary("testsourcelistingsit-content.kinihost.test", "source/test/test.html",
            "text/plain", 11, $subList[0]->getCreatedTime(), $subList[0]->getLastModifiedTime()), $subList[0]);

        $this->assertEquals(new StoredObjectSummary("testsourcelistingsit-content.kinihost.test", "source/test/test3.html",
            "text/plain", 14, $subList[1]->getCreatedTime(), $subList[1]->getLastModifiedTime()), $subList[1]);


    }


    public function testCanGetCurrentSiteConfig() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Create and activate the site.
        $site = $this->siteService->createSite(new SiteDescriptor("TestEntity2"));
        $this->siteService->activateSite($site->getSiteId());

        // Check when missing config that we simply assume a blank config
        $this->assertEquals(new SiteConfig(), $this->sourceService->getCurrentSiteConfig("testentity2"));


        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        // Test bad JSON config
        $changedObjects = [
            new ChangedObject("source/.kinihost-deploy", ChangedObject::CHANGE_TYPE_UPDATE, "BAD JSON", null, "65363465"),
        ];

        $contentRoot->replaceAll($changedObjects);

        $this->assertEquals(new SiteConfig(), $this->sourceService->getCurrentSiteConfig("testentity2"));


        // Test good JSON config
        $config = [
            "publishDirectory" => "public",
            "notFoundPage" => "notfound.html",
            "indexPage" => "index.php"
        ];

        $changedObjects = [
            new ChangedObject("source/.kinihost-deploy", ChangedObject::CHANGE_TYPE_UPDATE, json_encode($config), null, "65363465"),
        ];

        $contentRoot->replaceAll($changedObjects);

        $siteConfig = $this->sourceService->getCurrentSiteConfig("testentity2");

        $this->assertEquals(new SiteConfig("public", "index.php", "notfound.html"), $siteConfig);


    }


}
