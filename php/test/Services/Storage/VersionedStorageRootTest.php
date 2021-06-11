<?php


namespace Kinihost\Services\Storage;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\Exception\Storage\VersionDoesNotExistException;
use Kinihost\Services\Storage\StorageProvider\FileStorageProvider;
use Kinihost\Services\Storage\StorageProvider\StorageProvider;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;
use Kinihost\ValueObjects\Storage\Version;
use Kinihost\ValueObjects\Storage\VersionRestoreChangedObject;
use Kinihost\TestBase;

include_once __DIR__ . "/../../autoloader.php";

class VersionedStorageRootTest extends TestBase {

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
        $this->mockStorageProvider->returnValue("listObjects", [new StoredObjectSummary("test", "sub/tree/current/mine.pdf", "text/plain", 10, null, null), new StoredObjectSummary("test", "sub/tree/current/yours.pdf", "text/plain", 20, null, null)], ["test", "sub/tree/current"]);
        $this->mockStorageProvider->returnValue("getObject", new StoredObject("test", "sub/tree/current/mine.pdf", "text/plain", 10, null, null, "1234567890"), ["test", "sub/tree/current/mine.pdf"]);

    }


    public function testAllCRUDOperationsForVersionedStorageRootsAreAppliedToCurrentSubPath() {


        $storageRoot = new VersionedStorageRoot($this->mockStorageProvider, "test", "sub/tree");

        $this->assertEquals([new StoredObjectSummary("test", "mine.pdf", "text/plain", 10, null, null), new StoredObjectSummary("test", "yours.pdf", "text/plain", 20, null, null)],
            $storageRoot->listObjects());


        $this->assertEquals(new StoredObject("test", "mine.pdf", "text/plain", 10, null, null, "1234567890"),
            $storageRoot->getObject("mine.pdf"));


        $storageRoot->saveObject("sub/peanuts.txt", "Peanuts");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("saveObject", ["test", "sub/tree/current/sub/peanuts.txt", "Peanuts"]));

        $storageRoot->saveObjectFile("marko.txt", "Marko");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("saveObjectFile", ["test", "sub/tree/current/marko.txt", "Marko"]));

        $storageRoot->deleteObject("oldfile.txt");
        $this->assertTrue($this->mockStorageProvider->methodWasCalled("deleteObject", ["test", "sub/tree/current/oldfile.txt"]));


    }


    public function testCurrentVersionCreatedOnFirstSaveAndIncrementedOnSubsequentSuccessfulSavesAndDeletes() {

        $storageRoot = new VersionedStorageRoot("file", "versioned", "example");
        $storageRoot->create();


        // Zero should be current version when nothing created.
        $this->assertEquals(0, $storageRoot->getCurrentVersion());

        $this->assertFalse(file_exists("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));

        $storageRoot->saveObject("newfile.txt", "New File");

        $this->assertEquals(1, file_get_contents("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));
        $this->assertEquals(1, $storageRoot->getCurrentVersion());

        $storageRoot->saveObject("newfile.txt", "Later file");
        $this->assertEquals(2, file_get_contents("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));


        $storageRoot->saveObjectFile("tester.txt", __DIR__ . "/StorageProvider/example.txt");
        $this->assertEquals(3, file_get_contents("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));

        $storageRoot->deleteObject("tester.txt");
        $this->assertEquals(4, file_get_contents("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));

        try {
            $storageRoot->deleteObject("tester.txt");
        } catch (ObjectDoesNotExistException $e) {
        }

        $this->assertEquals(4, file_get_contents("FileStorage/versioned/example/current/" . VersionedStorageRoot::VERSION_FILENAME));

        // Check for current version
        $this->assertEquals(4, $storageRoot->getCurrentVersion());


    }


    public function testVersionsAreCreatedWithVersionDataOnSingleSavesAndDeletes() {

        $storageRoot = new VersionedStorageRoot("file", "versioned", "example2");
        $storageRoot->create();

        // Zero should be current version when nothing created.
        $this->assertEquals(0, $storageRoot->getCurrentVersion());
        $this->assertEquals([], $storageRoot->getPreviousVersions());

        $storageRoot->saveObject("newfile.txt", "New File");
        $this->assertEquals(1, $storageRoot->getCurrentVersion());
        $this->assertFalse(file_exists("FileStorage/versioned/example2/versions"));
        $this->assertEquals([], $storageRoot->getPreviousVersions());

        $storageRoot->saveObject("newfile.txt", "Modified File");
        $this->assertEquals(2, $storageRoot->getCurrentVersion());
        $this->assertTrue(file_exists("FileStorage/versioned/example2/versions/1/" . VersionedStorageRoot::CHANGES_FILENAME));
        $this->assertEquals("New File", file_get_contents("FileStorage/versioned/example2/versions/1/newfile.txt"));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_UPDATE, json_decode(file_get_contents("FileStorage/versioned/example2/versions/1/" . VersionedStorageRoot::CHANGES_FILENAME), true)["newfile.txt"]);

        $this->assertEquals(1, sizeof($storageRoot->getPreviousVersions()));
        $this->assertEquals(1, $storageRoot->getPreviousVersions()[0]->getVersion());
        $this->assertNotNull($storageRoot->getPreviousVersions()[0]->getCreatedDateTime());


        $storageRoot->saveObject("newobject.txt", "New Object File");
        $this->assertEquals(3, $storageRoot->getCurrentVersion());
        $this->assertTrue(file_exists("FileStorage/versioned/example2/versions/2/" . VersionedStorageRoot::CHANGES_FILENAME));
        $this->assertFalse(file_exists("FileStorage/versioned/example2/versions/2/newobject.txt"));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_DELETE, json_decode(file_get_contents("FileStorage/versioned/example2/versions/2/" . VersionedStorageRoot::CHANGES_FILENAME), true)["newobject.txt"]);


        $storageRoot->saveObjectFile("another-new.txt", __DIR__ . "/StorageProvider/example.txt");
        $this->assertEquals(4, $storageRoot->getCurrentVersion());
        $this->assertTrue(file_exists("FileStorage/versioned/example2/versions/3/" . VersionedStorageRoot::CHANGES_FILENAME));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_DELETE, json_decode(file_get_contents("FileStorage/versioned/example2/versions/3/" . VersionedStorageRoot::CHANGES_FILENAME), true)["another-new.txt"]);


        $storageRoot->deleteObject("newfile.txt");
        $this->assertEquals(5, $storageRoot->getCurrentVersion());
        $this->assertTrue(file_exists("FileStorage/versioned/example2/versions/4/" . VersionedStorageRoot::CHANGES_FILENAME));
        $this->assertEquals("Modified File", file_get_contents("FileStorage/versioned/example2/versions/4/newfile.txt"));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, json_decode(file_get_contents("FileStorage/versioned/example2/versions/4/" . VersionedStorageRoot::CHANGES_FILENAME), true)["newfile.txt"]);


    }


    public function testVersionsAreCreatedOnReplaceAllOperations() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "replaceall", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");


        $this->assertEquals(6, $storageRoot->getCurrentVersion());
        $this->assertEquals(5, sizeof($storageRoot->getPreviousVersions()));

        $newItems = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];

        // Replace with no objects
        $storageRoot->replaceAll($newItems);

        $this->assertEquals(7, $storageRoot->getCurrentVersion());
        $versionChanges = json_decode(file_get_contents("FileStorage/replaceall/content/versions/6/" . VersionedStorageRoot::CHANGES_FILENAME), true);

        $this->assertEquals(7, sizeof($versionChanges));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_UPDATE, $versionChanges["first.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["second.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["third.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["test/bongo.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["test/sub/new.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["test/sub/other.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_DELETE, $versionChanges["help/about.txt"]);

        $objects = $storageProvider->listObjects("replaceall", "content/versions/6");
        $this->assertEquals(5, sizeof($objects));

        // Check a couple
        $this->assertEquals("Bongo 123", $storageProvider->getObject("replaceall", "content/versions/6/first.txt")->getContent());
        $this->assertEquals("Bongo 123", $storageProvider->getObject("replaceall", "content/versions/6/second.txt")->getContent());
        $this->assertEquals("Bongo 123", $storageProvider->getObject("replaceall", "content/versions/6/test/sub/other.txt")->getContent());

    }


    public function testVersionsAreCreatedCorrectlyOnReplaceAllWithSubPath() {

        // Clean up to start with
        passthru("rm -rf FileStorage/replaceall");

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "replaceall", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");


        $this->assertEquals(6, $storageRoot->getCurrentVersion());
        $this->assertEquals(5, sizeof($storageRoot->getPreviousVersions()));

        $newItems = [new ChangedObject("new.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];

        // Replace with no objects
        $storageRoot->replaceAll($newItems, "test/sub");

        $this->assertEquals(7, $storageRoot->getCurrentVersion());
        $versionChanges = json_decode(file_get_contents("FileStorage/replaceall/content/versions/6/" . VersionedStorageRoot::CHANGES_FILENAME), true);

        $this->assertEquals(3, sizeof($versionChanges));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_UPDATE, $versionChanges["test/sub/new.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["test/sub/other.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_DELETE, $versionChanges["test/sub/help/about.txt"]);

        $objects = $storageProvider->listObjects("replaceall", "content/versions/6");
        $this->assertEquals(2, sizeof($objects));

        // Check the two expected items
        $this->assertEquals("Bongo 123", $storageProvider->getObject("replaceall", "content/versions/6/test/sub/new.txt")->getContent());
        $this->assertEquals("Bongo 123", $storageProvider->getObject("replaceall", "content/versions/6/test/sub/other.txt")->getContent());


    }


    public function testVersionsAreCreatedOnApplyChanges() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "applychanges", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "Bongo 123");
        $storageRoot->saveObject("third.txt", "Bongo 456");
        $storageRoot->saveObject("test/bongo.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/new.txt", "Bongo 123");
        $storageRoot->saveObject("test/sub/other.txt", "Bongo 123");


        $this->assertEquals(6, $storageRoot->getCurrentVersion());
        $this->assertEquals(5, sizeof($storageRoot->getPreviousVersions()));


        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_UPDATE, "PICKLES"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];

        // Replace with no objects
        $storageRoot->applyChanges($changes);

        $this->assertEquals(7, $storageRoot->getCurrentVersion());
        $versionChanges = json_decode(file_get_contents("FileStorage/applychanges/content/versions/6/" . VersionedStorageRoot::CHANGES_FILENAME), true);

        $this->assertEquals(4, sizeof($versionChanges));
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_UPDATE, $versionChanges["first.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_RESTORE, $versionChanges["second.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_UPDATE, $versionChanges["test/sub/other.txt"]);
        $this->assertEquals(VersionedStorageRoot::VERSION_OPERATION_DELETE, $versionChanges["help/about.txt"]);

       
    }


    public function testMissingFilesInContentAreIgnoredWhenCreatingVersions() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "missingfiles", "content");
        $storageRoot->create();
        $storageRoot->saveObject("first.txt", "Bongo 123");
        $storageRoot->saveObject("second.txt", "My Little Pony");

        // Now rudely delete the file from behind the back of the system
        unlink("FileStorage/missingfiles/content/current/second.txt");

        // Now attempt to remove a non existent file
        $changes = [new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_DELETE)];

        $storageRoot->applyChanges($changes);

        $this->assertTrue(true);


    }


    public function testCanGetChangedObjectsBackToVersion() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "changetracking", "content");
        $storageRoot->create();

        // Create some initial content
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_UPDATE, "SECONDFILE"),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_UPDATE, "PICKLES"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];
        $storageRoot->applyChanges($changes);


        // Create a new version
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "UPDATED-BOG"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA-2")];

        $storageRoot->applyChanges($changes);


        // Create a second version
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("test/sub/other2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "OTHER-2"),
            new ChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA-3")];

        $storageRoot->applyChanges($changes);


        // Check changes are created correctly back to previous version
        $this->assertEquals([
            new VersionRestoreChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, 2),
            new VersionRestoreChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_UPDATE, 2),
            new VersionRestoreChangedObject("test/sub/other2.txt", ChangedObject::CHANGE_TYPE_DELETE)
        ], $storageRoot->getChangesBackToPreviousVersion(2));


        $this->assertEquals([
            new VersionRestoreChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, 1),
            new VersionRestoreChangedObject("second.txt", ChangedObject::CHANGE_TYPE_UPDATE, 1),
            new VersionRestoreChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_UPDATE, 1),
            new VersionRestoreChangedObject("test/sub/other2.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new VersionRestoreChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_DELETE)
        ], $storageRoot->getChangesBackToPreviousVersion(1));


    }


    public function testCannotGetChangedObjectsBackToNonExistentVersion() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "changetrackingbad", "content");
        $storageRoot->create();

        // Create some initial content
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_UPDATE, "SECONDFILE"),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_UPDATE, "PICKLES"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];
        $storageRoot->applyChanges($changes);


        try {
            $storageRoot->getChangesBackToPreviousVersion(0);
            $this->fail("Should have thrown here");
        } catch (VersionDoesNotExistException $e) {
            $this->assertTrue(true);
        }


        try {
            $storageRoot->getChangesBackToPreviousVersion(1);
            $this->fail("Should have thrown here");
        } catch (VersionDoesNotExistException $e) {
            $this->assertTrue(true);
        }


        try {
            $storageRoot->getChangesBackToPreviousVersion(2);
            $this->fail("Should have thrown here");
        } catch (VersionDoesNotExistException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCanRevertToPreviousVersions() {

        /**
         * @var StorageProvider $storageProvider
         */
        $storageProvider = Container::instance()->get(FileStorageProvider::class);

        $storageRoot = new VersionedStorageRoot($storageProvider, "revertablechanges", "content");
        $storageRoot->create();


        // Create some initial content
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "BOGSTANDARD"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_UPDATE, "SECONDFILE"),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_UPDATE, "PICKLES"),
            new ChangedObject("help/about.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA")];
        $storageRoot->applyChanges($changes);


        // Create a new version
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_UPDATE, "UPDATED-BOG"),
            new ChangedObject("second.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("test/sub/other.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA-2")];

        $storageRoot->applyChanges($changes);


        // Create a second version
        $changes = [new ChangedObject("first.txt", ChangedObject::CHANGE_TYPE_DELETE),
            new ChangedObject("test/sub/other2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "OTHER-2"),
            new ChangedObject("help/about2.txt", ChangedObject::CHANGE_TYPE_UPDATE, "WONDERBAA-3")];

        $storageRoot->applyChanges($changes);

        // Back to version 1
        $storageRoot->revertToPreviousVersion(1);

        $this->assertEquals(4, $storageRoot->getCurrentVersion());

        $items = $storageRoot->getObjectFootprints();
        $this->assertEquals(4, sizeof($items));
        $this->assertEquals(md5("BOGSTANDARD"), $items["first.txt"]);
        $this->assertEquals(md5("SECONDFILE"), $items["second.txt"]);
        $this->assertEquals(md5("PICKLES"), $items["test/sub/other.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $items["help/about.txt"]);

        // Back to version 2
        $storageRoot->revertToPreviousVersion(2);

        $this->assertEquals(5, $storageRoot->getCurrentVersion());

        $items = $storageRoot->getObjectFootprints();
        $this->assertEquals(3, sizeof($items));
        $this->assertEquals(md5("UPDATED-BOG"), $items["first.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $items["help/about.txt"]);
        $this->assertEquals(md5("WONDERBAA-2"), $items["help/about2.txt"]);


        // Back to version 3
        $storageRoot->revertToPreviousVersion(3);

        $this->assertEquals(6, $storageRoot->getCurrentVersion());

        $items = $storageRoot->getObjectFootprints();
        $this->assertEquals(3, sizeof($items));
        $this->assertEquals(md5("OTHER-2"), $items["test/sub/other2.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $items["help/about.txt"]);
        $this->assertEquals(md5("WONDERBAA-3"), $items["help/about2.txt"]);


        // Back to version 4
        $storageRoot->revertToPreviousVersion(4);

        $items = $storageRoot->getObjectFootprints();
        $this->assertEquals(4, sizeof($items));
        $this->assertEquals(md5("BOGSTANDARD"), $items["first.txt"]);
        $this->assertEquals(md5("SECONDFILE"), $items["second.txt"]);
        $this->assertEquals(md5("PICKLES"), $items["test/sub/other.txt"]);
        $this->assertEquals(md5("WONDERBAA"), $items["help/about.txt"]);


    }

}
