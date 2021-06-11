<?php


namespace Kinihost\Services\Storage\StorageProvider;

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Storage\StorageClient;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinihost\ValueObjects\Storage\StorageProvider\HostedSiteConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\TestBase;

include_once __DIR__ . "/../../../../autoloader.php";

include_once "autoloader.php";

class GoogleCloudStorageProviderTest extends TestBase {

    /**
     * @var GoogleCloudStorageProvider
     */
    private $storageProvider;


    /**
     * @var StorageClient
     */
    private $storage;

    public function setUp(): void {
        $this->storageProvider = Container::instance()->getInterfaceImplementation(StorageProvider::class, "google");

        $objects = $this->storageProvider->listObjects("kinihost-unit-tests");
        foreach ($objects as $object) {
            $this->storageProvider->deleteObject($object->getContainerKey(), $object->getKey());
        }


        // Authenticate with google and get the storage service.
        $serviceBuilder = new ServiceBuilder([
            'keyFilePath' => $keyFilePath ?? Configuration::readParameter("google.keyfile.path")
        ]);

        $this->storage = $serviceBuilder->storage([
            "projectId" => $projectId ?? Configuration::readParameter("google.project.id")
        ]);


    }


    /**
     * Can create new container
     */
    public function testCanCreateUpdateAndRemoveNewContainersWithPublicAndPrivateAccess() {


        // Private bucket
        $containerKey = date("U") . ".kinihosttest.site";
        $this->storageProvider->createContainer($containerKey);

        // Check bucket exists
        $bucket = $this->storage->bucket($containerKey);
        $this->assertTrue($bucket->exists());

        // Check region matches configured region.
        $this->assertEquals(strtoupper(Configuration::readParameter("google.bucket.region")), $bucket->info()["location"]);

        // Check private (i.e. just default acl)
        $this->assertEquals(3, sizeof($bucket->acl()->get()));

        // Remove the bucket, check it is destroyed.
        $this->storageProvider->removeContainer($containerKey);
        $this->assertFalse($bucket->exists());


        // Public bucket
        $containerKey = date("U") . ".kinihosttest.site";
        $this->storageProvider->createContainer($containerKey, new StorageProviderConfig(true));


        // Check bucket exists
        $bucket = $this->storage->bucket($containerKey);
        $this->assertTrue($bucket->exists());

        $this->assertEquals("index.html", $bucket->info()["website"]["mainPageSuffix"]);
        $this->assertEquals("404.html", $bucket->info()["website"]["notFoundPage"]);

        // Check region matches configured region.
        $this->assertEquals(strtoupper(Configuration::readParameter("google.bucket.region")), $bucket->info()["location"]);

        // Check private (i.e. just default acl)
        $this->assertEquals(5, sizeof($bucket->iam()->policy()));


        // Update the container.
        $this->storageProvider->updateContainer($containerKey, new StorageProviderConfig(true, new HostedSiteConfig("wingit.html", "broken.html")));

        // Check bucket exists
        $bucket = $this->storage->bucket($containerKey);
        $this->assertTrue($bucket->exists());

        $this->assertEquals("wingit.html", $bucket->info()["website"]["mainPageSuffix"]);
        $this->assertEquals("broken.html", $bucket->info()["website"]["notFoundPage"]);


        // Remove the bucket, check it is destroyed.
        $this->storageProvider->removeContainer($containerKey);
        $this->assertFalse($bucket->exists());


    }


    public function testAlreadyExistentBucketsAreIgnoredOnCreate() {


        // Private bucket
        $containerKey = date("U") . ".kinihosttest.site";
        $this->storageProvider->createContainer($containerKey);

        // Repeat and confirm no errors
        $this->storageProvider->createContainer($containerKey);

        // Clear up afterwards.
        $this->storageProvider->removeContainer($containerKey);

        $this->assertTrue(true);

    }


    public function testCanPerformStandardOperationsForGoogleCloudStorage() {


        try {
            $this->storageProvider->createContainer("kinihost-unit-tests");
        } catch (\Exception $e) {
            // OK
        }

        // Save some objects
        $this->storageProvider->saveObject("kinihost-unit-tests", "test.txt", "Hello world");
        $this->storageProvider->saveObject("kinihost-unit-tests", "test.txt", "Ninja Warrior");
        $this->storageProvider->saveObject("kinihost-unit-tests", "my/fair/ladytest.txt", "Hello world");
        $this->storageProvider->saveObject("kinihost-unit-tests", "jumping/around/test.txt", "Hello world");


        $list = $this->storageProvider->listObjects("kinihost-unit-tests");

        $this->assertTrue(sizeof($list) > 2);
        $firstItem = $list[0];
        $this->assertEquals("kinihost-unit-tests", $firstItem->getContainerKey());
        $this->assertEquals("jumping", $firstItem->getKey());
        $this->assertEquals("folder", $firstItem->getContentType());
        $this->assertEquals(0, $firstItem->getSize());
        $this->assertNotNull($firstItem->getLastModifiedTime());
        $this->assertNotNull($firstItem->getCreatedTime());

        $thirdItem = $list[2];
        $this->assertEquals("kinihost-unit-tests", $thirdItem->getContainerKey());
        $this->assertEquals("test.txt", $thirdItem->getKey());
        $this->assertEquals("text/plain", $thirdItem->getContentType());
        $this->assertEquals(13, $thirdItem->getSize());
        $this->assertNotNull($thirdItem->getLastModifiedTime());
        $this->assertNotNull($thirdItem->getCreatedTime());


        $list = $this->storageProvider->listObjects("kinihost-unit-tests", "my/fair");


        $this->assertEquals(1, sizeof($list));
        $firstItem = $list[0];
        $this->assertEquals("kinihost-unit-tests", $firstItem->getContainerKey());
        $this->assertEquals("my/fair/ladytest.txt", $firstItem->getKey());
        $this->assertEquals("text/plain", $firstItem->getContentType());
        $this->assertEquals(11, $firstItem->getSize());
        $this->assertNotNull($firstItem->getLastModifiedTime());
        $this->assertNotNull($firstItem->getCreatedTime());


        $singleItem = $this->storageProvider->getObject("kinihost-unit-tests", "test.txt");

        $this->assertEquals("kinihost-unit-tests", $singleItem->getContainerKey());
        $this->assertEquals("test.txt", $singleItem->getKey());
        $this->assertEquals("text/plain", $singleItem->getContentType());
        $this->assertEquals(13, $singleItem->getSize());
        $this->assertNotNull($singleItem->getLastModifiedTime());
        $this->assertNotNull($singleItem->getCreatedTime());
        $this->assertEquals("Ninja Warrior", $singleItem->getContent());


        $this->storageProvider->deleteObject("kinihost-unit-tests", "test.txt");
        try {
            $this->assertFalse(file_exists("gs://oxfordcyber-test/kinihost-unit-tests/test.txt"));
        } catch (NotFoundException $e) {
            // Fine
        }


        $this->storageProvider->deleteObject("kinihost-unit-tests", "my/fair");

        try {
            $this->assertFalse(file_exists("gs://oxfordcyber-test/kinihost-unit-tests/my/fair/ladytest.txt"));
        } catch (NotFoundException $e) {
            // Fine
        }


        $this->storageProvider->copyObject("kinihost-unit-tests", "jumping/around/test.txt", "jumping/around/petrol.txt");
        $this->assertTrue(file_exists("gs://kinihost-unit-tests/jumping/around/petrol.txt"));
        $this->assertEquals("Hello world", file_get_contents("gs://kinihost-unit-tests/jumping/around/petrol.txt"));


    }


    public function testCanGetDirectUploadURLForAFile() {


        try {
            $this->storageProvider->createContainer("kinihost-unit-tests");
        } catch (\Exception $e) {
            // OK
        }

        $uploadUrl = $this->storageProvider->getDirectUploadURL("kinihost-unit-tests", "hello/mine.txt");

        $this->assertTrue(strpos($uploadUrl, "storage.googleapis.com") > 0);
        $this->assertTrue(strpos($uploadUrl, "kinihost-unit-tests/hello/mine.txt") > 0);


    }


    public function testHTMLFilesHaveCachingOverriddenWithConfiguredCacheTimeWhenWritten() {


        try {
            $this->storageProvider->createContainer("kinihost-unit-tests");
        } catch (\Exception $e) {
            // OK
        }

        $this->storageProvider->saveObject("kinihost-unit-tests", "mynewfile.html", "<html><body>Hello</body></html>");
        $this->assertEquals("public,max-age=120", $this->storage->bucket("kinihost-unit-tests")->object("mynewfile.html")->info()["cacheControl"] ?? null);


        $this->storageProvider->saveObjectFile("kinihost-unit-tests", "bingobongo.htm", __DIR__ . "/example.htm");
        $this->assertEquals("public,max-age=120", $this->storage->bucket("kinihost-unit-tests")->object("bingobongo.htm")->info()["cacheControl"] ?? null);

        $this->storageProvider->copyObject("kinihost-unit-tests", "bingobongo.htm", "my/tree/bingobongo.html");
        $this->assertEquals("public,max-age=120", $this->storage->bucket("kinihost-unit-tests")->object("my/tree/bingobongo.html")->info()["cacheControl"] ?? null);

        // Now confirm that non html retains cache control
        $this->storageProvider->saveObjectFile("kinihost-unit-tests", "bingobongo.txt", __DIR__ . "/example.txt");
        $this->assertEquals("", $this->storage->bucket("kinihost-unit-tests")->object("bingobongo.txt")->info()["cacheControl"] ?? null);


    }

    public function testJSONFilesHaveZeroCacheTimeAlways() {

        try {
            $this->storageProvider->createContainer("kinihost-unit-tests");
        } catch (\Exception $e) {
            // OK
        }

        $this->storageProvider->saveObject("kinihost-unit-tests", "mynewfile.json", "{}");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("mynewfile.json")->info()["cacheControl"] ?? null);


        $this->storageProvider->saveObjectFile("kinihost-unit-tests", "bingobongo.json", __DIR__ . "/example.json");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("bingobongo.json")->info()["cacheControl"] ?? null);

        $this->storageProvider->copyObject("kinihost-unit-tests", "bingobongo.json", "my/tree/bingobongo.json");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("my/tree/bingobongo.json")->info()["cacheControl"] ?? null);

    }

    public function testAllFilesHaveZeroCacheTimeIfCachingDisabled() {
        try {
            $this->storageProvider->createContainer("kinihost-unit-tests");
        } catch (\Exception $e) {
            // OK
        }

        $this->storageProvider->setCaching(false);

        $this->storageProvider->saveObject("kinihost-unit-tests", "mynewfile.json", "{}");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("mynewfile.json")->info()["cacheControl"] ?? null);

        $this->storageProvider->saveObject("kinihost-unit-tests", "mynewfile.html", "<h1>Bingo</h1>");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("mynewfile.html")->info()["cacheControl"] ?? null);

        $this->storageProvider->saveObjectFile("kinihost-unit-tests", "bingobongo.txt", __DIR__ . "/example.txt");
        $this->assertEquals("public,max-age=0", $this->storage->bucket("kinihost-unit-tests")->object("bingobongo.txt")->info()["cacheControl"] ?? null);


    }



}
