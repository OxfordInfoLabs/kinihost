<?php

namespace Kinihost\Services\Storage;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\Services\Storage\StorageProvider\StorageProvider;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\ValueObjects\Storage\ChangeResult;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;
use Kinihost\TestBase;

include_once "autoloader.php";

class StorageRootTest extends TestBase {

    /**
     * @var MockObject
     */
    private $mockStorageProvider;

    public function setUp(): void {

        /**
         * @var $mockObjectProvider MockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->mockStorageProvider = $mockObjectProvider->getMockInstance(StorageProvider::class);
        $this->mockStorageProvider->returnValue("listObjects", [new StoredObjectSummary("test", "sub/tree/mine.pdf", "text/plain", 10, null, null), new StoredObjectSummary("test", "sub/tree/yours.pdf", "text/plain", 20, null, null)], ["test", "sub/tree"]);
        $this->mockStorageProvider->returnValue("getObject", new StoredObject("test", "sub/tree/mine.pdf", "text/plain", 10, null, null, "1234567890"), ["test", "sub/tree/mine.pdf"]);
        $this->mockStorageProvider->returnValue("getObjectContents", "1234567890", ["test", "sub/tree/mine.pdf"]);
    }


    public function testStorageRootDelegatesBasicCRUDFunctionsStraightToProvider() {

        $storageRoot = new StorageRoot($this->mockStorageProvider, "test", "sub/tree");

        $this->assertEquals([new StoredObjectSummary("test", "mine.pdf", "text/plain", 10, null, null), new StoredObjectSummary("test", "yours.pdf", "text/plain", 20, null, null)],
            $storageRoot->listObjects());


        $this->assertEquals(new StoredObject("test", "mine.pdf", "text/plain", 10, null, null, "1234567890"),
            $storageRoot->getObject("mine.pdf"));

        $this->assertEquals("1234567890", $storageRoot->getObjectContents("mine.pdf"));


        $storageRoot->saveObject("sub/peanuts.txt", "Peanuts");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("saveObject", ["test", "sub/tree/sub/peanuts.txt", "Peanuts"]));

        $storageRoot->saveObjectFile("marko.txt", "Marko");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("saveObjectFile", ["test", "sub/tree/marko.txt", "Marko"]));

        $storageRoot->deleteObject("oldfile.txt");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("deleteObject", ["test", "sub/tree/oldfile.txt"]));


    }


    public function testGetDirectUploadURLDelegatesStraightToProvider() {

        $storageRoot = new StorageRoot($this->mockStorageProvider, "test", "sub/tree");

        $storageRoot->getDirectUploadURL("my/little/pony.txt");

        $this->assertTrue($this->mockStorageProvider->methodWasCalled("getDirectUploadURL", ["test", "sub/tree/my/little/pony.txt"]));


    }


    public function testGetDirectDownloadURLDelegatesStraightToProvider() {

        $storageRoot = new StorageRoot($this->mockStorageProvider, "test", "sub/tree");

        $storageRoot->getDirectDownloadURL("my/little/pony.txt");

        $this->assertTrue($this->mockStorageProvider->methodWasCalled("getDirectDownloadURL", ["test", "sub/tree/my/little/pony.txt"]));

    }


    public function testSavingNewObjectsUpdatesFootprintFile() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->getInterfaceImplementation(StorageProvider::class, "file");

        $storageRoot = new StorageRoot("file", "test", "content");
        $storageRoot->create();

        $storageRoot->saveObject("bongo.txt", "Bongo 123");

        // Grab the footprint file
        $footprintFile = $storageProvider->getObject("test", "content/" . StorageRoot::FOOTPRINT_FILENAME);

        // Decode
        $footprints = json_decode($footprintFile->getContent(), true);
        $this->assertEquals(md5("Bongo 123"), $footprints["bongo.txt"]);

        $objectFootprints = $storageRoot->getObjectFootprints();
        $this->assertEquals($footprints, $objectFootprints);


        $storageRoot->saveObject("bongo.txt", "Bongo 456");
        $this->assertEquals(md5("Bongo 456"), $storageRoot->getObjectFootprints()["bongo.txt"]);


        $storageRoot->saveObject("nested/deep/down/in/the/folder/structure/bongo.txt", "Bongo 456");
        $this->assertEquals(md5("Bongo 456"), $storageRoot->getObjectFootprints()["nested/deep/down/in/the/folder/structure/bongo.txt"]);


        $storageRoot->saveObjectFile("example.txt", __DIR__ . "/StorageProvider/example.txt");
        $this->assertEquals(md5(file_get_contents(__DIR__ . "/StorageProvider/example.txt")), $storageRoot->getObjectFootprints()["example.txt"]);


    }


    public function testCanGetObjectFootprintsForSubPrefix() {


        $storageRoot = new StorageRoot("file", "test-with-sub", "content");
        $storageRoot->create();

        $storageRoot->saveObject("sub1/hello", "Hello");
        $storageRoot->saveObject("sub1/sub2/goodbye", "Goodbye");
        $storageRoot->saveObject("sub2/swimming", "Swimming");
        $storageRoot->saveObject("sub2/shopping", "Shopping");


        $this->assertEquals(["hello" => md5("Hello"), "sub2/goodbye" => md5("Goodbye")], $storageRoot->getObjectFootprints("sub1"));
        $this->assertEquals(["goodbye" => md5("Goodbye")], $storageRoot->getObjectFootprints("sub1/sub2"));
        $this->assertEquals(["swimming" => md5("Swimming"), "shopping" => md5("Shopping")], $storageRoot->getObjectFootprints("sub2"));


    }


    public function testRemovingObjectsUpdatesFootprintFile() {

        $storageRoot = new StorageRoot("file", "test", "content");
        $storageRoot->create();
        $storageRoot->saveObject("bongo.txt", "Bongo 123");

        $this->assertEquals(md5("Bongo 123"), $storageRoot->getObjectFootprints()["bongo.txt"]);

        $storageRoot->deleteObject("bongo.txt");

        $this->assertFalse(isset($storageRoot->getObjectFootprints()["bongo.txt"]));


        // Check recursive removals when a folder removed
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");

        $this->assertEquals(md5("Bongo 123"), $storageRoot->getObjectFootprints()["test/bongo.txt"]);
        $this->assertEquals(md5("Bongo 123"), $storageRoot->getObjectFootprints()["test/sub/new.txt"]);
        $this->assertEquals(md5("Bongo 123"), $storageRoot->getObjectFootprints()["test/sub/other.txt"]);

        $storageRoot->deleteObject("test");

        $this->assertFalse(isset($storageRoot->getObjectFootprints()["test/bongo.txt"]));
        $this->assertFalse(isset($storageRoot->getObjectFootprints()["test/sub/new.txt"]));
        $this->assertFalse(isset($storageRoot->getObjectFootprints()["test/sub/other.txt"]));


    }


    public function testCanReplaceAllUsingBlankChangeObjects() {

        passthru("rm -rf FileStorage/new");

        $storageRoot = new StorageRoot("file", "new", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");

        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(6, sizeof($storageRoot->getObjectFootprints()));

        // Replace with no objects
        $storageRoot->replaceAll([]);

        $this->assertEquals(0, sizeof($storageRoot->getObjectFootprints()));


    }


    public function testCanReplaceAllWithNewItems() {

        passthru("rm -rf FileStorage/new");

        $storageRoot = new StorageRoot("file", "new", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");

        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(6, sizeof($storageRoot->getObjectFootprints()));


        $newItems = [new ChangedObject("bandbang.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];

        // Replace with no objects
        $storageRoot->replaceAll($newItems);

        $this->assertEquals(3, sizeof($storageRoot->listObjects()));
        $this->assertEquals(2, sizeof($storageRoot->getObjectFootprints()));

        $this->assertEquals(md5("BOGSTANDARD"), $storageRoot->getObjectFootprints()["bandbang.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $storageRoot->getObjectFootprints()["help/about.txt"]);

        $this->assertEquals("BOGSTANDARD", $storageRoot->getObject("bandbang.txt")->getContent());
        $this->assertEquals("WONDERBAA", $storageRoot->getObject("help/about.txt")->getContent());


    }


    public function testCanReplaceAllAtSubPathIfSuppliedAndOtherItemsLeftIntact() {

        passthru("rm -rf FileStorage/new");

        $storageRoot = new StorageRoot("file", "new", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");

        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(6, sizeof($storageRoot->getObjectFootprints()));


        $newItems = [new ChangedObject(
            "bandbang.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA"),
            new ChangedObject("new.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];

        // Replace with no objects
        $storageRoot->replaceAll($newItems, "test/sub");

        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(7, sizeof($storageRoot->getObjectFootprints()));

        // Check original content intact
        $this->assertEquals("Bongo 123", $storageRoot->getObjectContents("first.txt"));
        $this->assertEquals("Bongo 123", $storageRoot->getObjectContents("second.txt"));
        $this->assertEquals("Bongo 456", $storageRoot->getObjectContents("third.txt"));
        $this->assertEquals("Bongo 123", $storageRoot->getObjectContents("test/bongo.txt"));

        $this->assertEquals("WONDERBAA", $storageRoot->getObjectContents("test/sub/new.txt"));
        $this->assertEquals(md5("BOGSTANDARD"), $storageRoot->getObjectFootprints()["test/sub/bandbang.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $storageRoot->getObjectFootprints()["test/sub/help/about.txt"]);

        $this->assertEquals("BOGSTANDARD", $storageRoot->getObject("test/sub/bandbang.txt")->getContent());
        $this->assertEquals("WONDERBAA", $storageRoot->getObject("test/sub/help/about.txt")->getContent());


    }


    public function testCanUpdateWithChangedFiles() {

        passthru("rm -rf FileStorage/changes");


        $storageRoot = new StorageRoot("file", "changes", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");

        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(6, sizeof($storageRoot->getObjectFootprints()));


        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_UPDATE, "Bongo 999"),
            new ChangedObject("third.txt", ChangedObject::CHANGE_TYPE_UPDATE, "Bongo 123", null, md5("Bongo 456")),
            new ChangedObject("fourth.txt", ChangedObject::CHANGE_TYPE_UPDATE, "NEW CONTENT"),
            new ChangedObject("test/new.txt", ChangedObject::CHANGE_TYPE_UPDATE, null, __DIR__ . "/StorageProvider/example.txt"),
            new ChangedObject("nonexistent", ChangedObject::CHANGE_TYPE_DELETE)
        ];

        $changeResult = $storageRoot->applyChanges($changes);
        $this->assertTrue($changeResult instanceof ChangeResult);

        $this->assertEquals(["first.txt"], $changeResult->getDeleted());
        $this->assertEquals(["second.txt"], $changeResult->getUpdated());
        $this->assertEquals(["fourth.txt", "test/new.txt"], $changeResult->getCreated());
        $this->assertEquals(["nonexistent" => ChangeResult::FAILED_DELETE_NOT_FOUND], $changeResult->getFailed());


        $this->assertEquals(4, sizeof($storageRoot->listObjects()));
        $this->assertEquals(7, sizeof($storageRoot->getObjectFootprints()));

        // Check that updates occurred
        try {
            $storageRoot->getObject("first.txt");
            $this->fail("Should have thrown here");
        } catch (ObjectDoesNotExistException $e) {
            // Success
        }


        // Check second updated
        $this->assertEquals("Bongo 999", $storageRoot->getObject("second.txt")->getContent());

        // Check new ones created
        $this->assertEquals("NEW CONTENT", $storageRoot->getObject("fourth.txt")->getContent());
        $this->assertEquals(file_get_contents(__DIR__ . "/StorageProvider/example.txt"), $storageRoot->getObject("test/new.txt")->getContent());


        // Check third didn't update due to an optimised md5 hash.
        $this->assertEquals("Bongo 456", $storageRoot->getObject("third.txt")->getContent());


    }


    public function testCanSynchroniseWithOtherStorageRoot() {

        passthru("rm -rf FileStorage/sychronise*");


        $storageRoot1 = new StorageRoot("file", "synchronise1");
        $storageRoot1->create();

        $storageRoot1->saveObject("test.html", "Hello world of testing and sync");
        $storageRoot1->saveObject("nested/deep/test2.html", "Wonderful world of fun and games");
        $storageRoot1->saveObject("test2.html", "Good morning");

        $storageRoot2 = new StorageRoot("file", "synchronise2");
        $storageRoot2->create();

        // Synchronise
        $storageRoot2->synchronise($storageRoot1);

        // Check new files exist.
        $this->assertEquals(3, sizeof($storageRoot2->getObjectFootprints()));
        $this->assertEquals("Hello world of testing and sync", $storageRoot2->getObject("test.html")->getContent());
        $this->assertEquals("Wonderful world of fun and games", $storageRoot2->getObject("nested/deep/test2.html")->getContent());
        $this->assertEquals("Good morning", $storageRoot2->getObject("test2.html")->getContent());


        // Make changes
        $storageRoot1->deleteObject("test.html");
        $storageRoot1->saveObject("test3.html", "Wonder wall");


        // Synchronise
        $storageRoot2->synchronise($storageRoot1);

        // Check new files exist.
        $this->assertEquals(3, sizeof($storageRoot2->getObjectFootprints()));
        $this->assertEquals("Wonder wall", $storageRoot2->getObject("test3.html")->getContent());
        $this->assertEquals("Wonderful world of fun and games", $storageRoot2->getObject("nested/deep/test2.html")->getContent());
        $this->assertEquals("Good morning", $storageRoot2->getObject("test2.html")->getContent());

        $this->assertFalse(file_exists("FileStorage/synchronise2/test.html"));


    }


    public function testCanSynchroniseSubTreeWithOtherRoot() {


        passthru("rm -rf FileStorage/synchronise*");


        $storageRoot1 = new StorageRoot("file", "synchronise1");
        $storageRoot1->create();

        $storageRoot1->saveObject("test.html", "Hello world of testing and sync");
        $storageRoot1->saveObject("nested/deep/test2.html", "Wonderful world of fun and games");
        $storageRoot1->saveObject("test2.html", "Good morning");

        $storageRoot2 = new StorageRoot("file", "synchronise2");
        $storageRoot2->create();

        // Add some static files outside storage root sub
        $storageRoot2->saveObject("toplevel.html", "Hello I'm top level");

        // Synchronise
        $storageRoot2->synchronise($storageRoot1, "sub");

        // Check new files exist.
        $this->assertEquals(4, sizeof($storageRoot2->getObjectFootprints()));
        $this->assertEquals("Hello I'm top level", $storageRoot2->getObject("toplevel.html")->getContent());
        $this->assertEquals("Hello world of testing and sync", $storageRoot2->getObject("sub/test.html")->getContent());
        $this->assertEquals("Wonderful world of fun and games", $storageRoot2->getObject("sub/nested/deep/test2.html")->getContent());
        $this->assertEquals("Good morning", $storageRoot2->getObject("sub/test2.html")->getContent());


        // Make changes
        $storageRoot1->deleteObject("test.html");
        $storageRoot1->saveObject("test3.html", "Wonder wall");


        // Synchronise
        $storageRoot2->synchronise($storageRoot1, "sub");

        // Check new files exist.
        $this->assertEquals(4, sizeof($storageRoot2->getObjectFootprints()));
        $this->assertEquals("Hello I'm top level", $storageRoot2->getObject("toplevel.html")->getContent());
        $this->assertEquals("Wonder wall", $storageRoot2->getObject("sub/test3.html")->getContent());
        $this->assertEquals("Wonderful world of fun and games", $storageRoot2->getObject("sub/nested/deep/test2.html")->getContent());
        $this->assertEquals("Good morning", $storageRoot2->getObject("sub/test2.html")->getContent());

        $this->assertFalse(file_exists("FileStorage/synchronise2/sub/test.html"));


    }


    public function testCanSynchroniseSubTreeOfOtherRoot() {

        passthru("rm -rf FileStorage/synchronise*");

        $storageRoot1 = new StorageRoot("file", "synchronise1");
        $storageRoot1->create();

        $storageRoot1->saveObject("test.html", "Hello world of testing and sync");
        $storageRoot1->saveObject("nested/deep/test2.html", "Wonderful world of fun and games");
        $storageRoot1->saveObject("nested/test2.html", "Good morning");

        $storageRoot2 = new StorageRoot("file", "synchronise2");
        $storageRoot2->create();

        // Synchronise
        $storageRoot2->synchronise($storageRoot1, null, "nested");

        // Check new files exist at top level from the sub tree
        $this->assertEquals(2, sizeof($storageRoot2->getObjectFootprints()));
        $this->assertEquals("Wonderful world of fun and games", $storageRoot2->getObject("deep/test2.html")->getContent());
        $this->assertEquals("Good morning", $storageRoot2->getObject("test2.html")->getContent());


    }


}
