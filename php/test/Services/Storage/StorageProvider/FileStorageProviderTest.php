<?php

namespace Kinihost\Services\Storage\StorageProvider;

use Kinikit\Core\Configuration\Configuration;
use Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException;
use Kinihost\Exception\Storage\StorageProvider\InvalidFileStorageRootException;
use Kinihost\Exception\Storage\StorageProvider\MissingFileStorageRootException;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;
use Kinihost\TestBase;

include_once "autoloader.php";

class FileStorageProviderTest extends TestBase {

    /**
     * @var FileStorageProvider
     */
    private $fileStorageProvider;

    public function setUp(): void {
        $this->fileStorageProvider = new FileStorageProvider();
        passthru("rm -rf FileStorage/*");
    }


    public function testExceptionRaisedIfMissingOrBadFileStorageRootSupplied() {

        Configuration::instance()->addParameter("file.storage.root", null);

        try {
            new FileStorageProvider();
            $this->fail("Should have thrown here");
        } catch (MissingFileStorageRootException $e) {
            // Success
        }


        try {
            new FileStorageProvider("/bingo");
            $this->fail("Should have thrown here");
        } catch (InvalidFileStorageRootException $e) {
            // Success
        }

        Configuration::instance(true);

        $this->assertTrue(true);

    }

    public function testCanCreateNewContainers() {

        $this->fileStorageProvider->createContainer("shopping");

        $this->assertTrue(file_exists("FileStorage/shopping"));
        $this->assertTrue(is_dir("FileStorage/shopping"));


        $this->fileStorageProvider->createContainer("food");
        $this->assertTrue(file_exists("FileStorage/food"));
        $this->assertTrue(is_dir("FileStorage/food"));

    }


    public function testCanRemoveContainersProvidedTheyExist() {

        try {
            $this->fileStorageProvider->removeContainer("invisible");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->createContainer("shopping");
        $this->fileStorageProvider->removeContainer("shopping");

        $this->assertFalse(file_exists("FileStorage/shopping"));

    }


    public function testCanSaveObjectsProvidedContainerExists() {

        try {
            $this->fileStorageProvider->saveObject("invisible", "test", "Hello");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }

        $this->fileStorageProvider->createContainer("shipping");
        $this->fileStorageProvider->saveObject("shipping", "myship.txt", "Hello baby");
        $this->fileStorageProvider->saveObject("shipping", "navigation/radar/radar.pdf", "Hello PDF MAN");

        $this->assertTrue(file_exists("FileStorage/shipping/myship.txt"));
        $this->assertEquals("Hello baby", file_get_contents("FileStorage/shipping/myship.txt"));

        $this->assertTrue(file_exists("FileStorage/shipping/navigation/radar/radar.pdf"));
        $this->assertEquals("Hello PDF MAN", file_get_contents("FileStorage/shipping/navigation/radar/radar.pdf"));


    }


    public function testCanSaveObjectsFromLocalFileProvidedContainerExists() {

        try {
            $this->fileStorageProvider->saveObjectFile("invisible", "test", __DIR__ . "/example.txt");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }

        $this->fileStorageProvider->createContainer("files");

        $footprint = $this->fileStorageProvider->saveObjectFile("files", "test.txt", __DIR__ . "/example.txt");
        $this->assertEquals(md5(file_get_contents(__DIR__ . "/example.txt")), $footprint);

        $footprint = $this->fileStorageProvider->saveObjectFile("files", "nested/sub/test.txt", __DIR__ . "/example.txt");
        $this->assertEquals(md5(file_get_contents(__DIR__ . "/example.txt")), $footprint);


        $this->assertTrue(file_exists("FileStorage/files/test.txt"));
        $this->assertEquals("Bingo Bongo", trim(file_get_contents("FileStorage/files/test.txt")));

        $this->assertTrue(file_exists("FileStorage/files/nested/sub/test.txt"));
        $this->assertEquals("Bingo Bongo", trim(file_get_contents("FileStorage/files/nested/sub/test.txt")));

    }

    public function testCanDeleteObjectsProvidedContainerExists() {

        try {
            $this->fileStorageProvider->deleteObject("invisible", "test");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }

        $this->fileStorageProvider->createContainer("deletable");


        $this->fileStorageProvider->saveObject("deletable", "bobby.txt", "Bobby");
        $this->assertTrue(file_exists("FileStorage/deletable/bobby.txt"));

        $this->fileStorageProvider->deleteObject("deletable", "bobby.txt");
        $this->assertFalse(file_exists("FileStorage/deletable/bobby.txt"));

    }


    public function testCanGetListsOfFolderObjectsAsStoredObjectSummaries() {


        try {
            $this->fileStorageProvider->listObjects("invisible", "test");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->createContainer("documents");


        try {
            $this->fileStorageProvider->listObjects("documents", "test");
            $this->fail("Should have thrown here");
        } catch (ObjectDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->saveObject("documents", "test.txt", "Bingo Bango");
        $this->fileStorageProvider->saveObject("documents", "test.pdf", "Bingo PDF");
        $this->fileStorageProvider->saveObject("documents", "archive/test.txt", "Archive Bingo PDF");
        $this->fileStorageProvider->saveObject("documents", "archive/test.pdf", "Archive Bingo PDF");


        $list = $this->fileStorageProvider->listObjects("documents");
        $this->assertEquals(3, sizeof($list));
        $this->assertEquals(new StoredObjectSummary("documents", "archive", "folder", 0, $list[0]->getCreatedTime(), $list[0]->getLastModifiedTime()), $list[0]);
        $this->assertEquals(new StoredObjectSummary("documents", "test.pdf", "text/plain", 9, $list[1]->getCreatedTime(), $list[1]->getLastModifiedTime()), $list[1]);
        $this->assertEquals(new StoredObjectSummary("documents", "test.txt", "text/plain", 11, $list[2]->getCreatedTime(), $list[2]->getLastModifiedTime()), $list[2]);

        $list = $this->fileStorageProvider->listObjects("documents", "archive");
        $this->assertEquals(2, sizeof($list));
        $this->assertEquals(new StoredObjectSummary("documents", "archive/test.pdf", "text/plain", 17, $list[0]->getCreatedTime(), $list[0]->getLastModifiedTime()), $list[0]);
        $this->assertEquals(new StoredObjectSummary("documents", "archive/test.txt", "text/plain", 17, $list[1]->getCreatedTime(), $list[1]->getLastModifiedTime()), $list[1]);

    }


    public function testCanGetSingleObjectAsStoredObject() {

        try {
            $this->fileStorageProvider->getObject("invisible", "test");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->createContainer("documents");


        try {
            $this->fileStorageProvider->getObject("documents", "test");
            $this->fail("Should have thrown here");
        } catch (ObjectDoesNotExistException $e) {
            // Success
        }

        $this->fileStorageProvider->saveObject("documents", "test.pdf", "Bingo PDF");
        $this->fileStorageProvider->saveObject("documents", "archive/test.txt", "Archive Bingo PDF");


        $object = $this->fileStorageProvider->getObject("documents", "test.pdf");
        $this->assertEquals(new StoredObject("documents", "test.pdf", "text/plain", 9, $object->getCreatedTime(), $object->getLastModifiedTime(), "Bingo PDF"), $object);

        $object = $this->fileStorageProvider->getObject("documents", "archive/test.txt");
        $this->assertEquals(new StoredObject("documents", "archive/test.txt", "text/plain", 17, $object->getCreatedTime(), $object->getLastModifiedTime(), "Archive Bingo PDF"), $object);


    }


    public function testCanGetSingleObjectContentsAsString() {

        try {
            $this->fileStorageProvider->getObjectContents("invisible", "test");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->createContainer("documents");


        try {
            $this->fileStorageProvider->getObjectContents("documents", "test");
            $this->fail("Should have thrown here");
        } catch (ObjectDoesNotExistException $e) {
            // Success
        }

        $this->fileStorageProvider->saveObject("documents", "test.pdf", "Bingo PDF");
        $this->fileStorageProvider->saveObject("documents", "archive/test.txt", "Archive Bingo PDF");


        $object = $this->fileStorageProvider->getObjectContents("documents", "test.pdf");
        $this->assertEquals("Bingo PDF", $object);

        $object = $this->fileStorageProvider->getObjectContents("documents", "archive/test.txt");
        $this->assertEquals("Archive Bingo PDF", $object);



    }


    public function testCanCopyObjectsWithinContainerProvidedContainerAndObjectExist() {


        try {
            $this->fileStorageProvider->copyObject("invisible", "test", "newtest");
            $this->fail("Should have thrown here");
        } catch (ContainerDoesNotExistException $e) {
            // Success
        }


        $this->fileStorageProvider->createContainer("copyroot");


        try {
            $this->fileStorageProvider->copyObject("copyroot", "test", "newtest");
            $this->fail("Should have thrown here");
        } catch (ObjectDoesNotExistException $e) {
            // Success
        }


        // Create a new object
        $this->fileStorageProvider->saveObject("copyroot", "test.pdf", "Bingo PDF");


        // Copy it
        $this->fileStorageProvider->copyObject("copyroot", "test.pdf", "copy.pdf");
        $this->fileStorageProvider->copyObject("copyroot", "copy.pdf", "sub/tree/copy/copy.pdf");

        $this->assertEquals("Bingo PDF", $this->fileStorageProvider->getObject("copyroot", "copy.pdf")->getContent());
        $this->assertEquals("Bingo PDF", $this->fileStorageProvider->getObject("copyroot", "sub/tree/copy/copy.pdf")->getContent());


    }


    public function testCanGetFileSystemPathToObject() {

        $path = $this->fileStorageProvider->getFileSystemPath("mycontainer", "hello/world.txt");

        $this->assertEquals("FileStorage/mycontainer/hello/world.txt", $path);

    }

}
