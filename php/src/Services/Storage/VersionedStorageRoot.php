<?php


namespace Kinihost\Services\Storage;


use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Kinikit\Core\Asynchronous\AsynchronousFunction;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Asynchronous\Processor\SynchronousProcessor;
use Kinikit\Core\DependencyInjection\Container;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\Exception\Storage\VersionDoesNotExistException;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;
use Kinihost\ValueObjects\Storage\Version;
use Kinihost\ValueObjects\Storage\VersionRestoreChangedObject;

/**
 * Overlayed versioned storage root
 *
 * Class VersionedStorageRoot
 * @package Kinihost\Services\Storage
 */
class VersionedStorageRoot extends StorageRoot {

    /**
     * @var string
     */
    private $basePath;

    // Current prefix
    const CURRENT_PREFIX = "current";

    const VERSION_FILENAME = ".oc-version";
    const CHANGES_FILENAME = ".oc-version-changes";

    const VERSION_OPERATION_RESTORE = "RESTORE";
    const VERSION_OPERATION_DELETE = "DELETE";
    const VERSION_OPERATION_UPDATE = "UPDATE";


    public function __construct($storageProviderKey, $containerKey, $path = "") {
        $this->basePath = $path;
        parent::__construct($storageProviderKey, $containerKey, $path ? $path . "/current" : "current");
    }

    public function saveObject($objectPath, $objectContent) {
        $this->createVersion([new ChangedObject($objectPath, ChangedObject::CHANGE_TYPE_UPDATE)]);
        parent::saveObject($objectPath, $objectContent);
    }

    public function saveObjectFile($objectPath, $localFilePath) {
        $this->createVersion([new ChangedObject($objectPath, ChangedObject::CHANGE_TYPE_UPDATE)]);
        parent::saveObjectFile($objectPath, $localFilePath);
    }

    public function deleteObject($objectPath) {
        $this->createVersion([new ChangedObject($objectPath, ChangedObject::CHANGE_TYPE_DELETE)]);
        parent::deleteObject($objectPath);
    }

    /**
     * Replace all with the supplied changed objects
     *
     * @param ChangedObject[] $newObjects
     */
    public function replaceAll($newObjects, $subPath = "") {

        // Grab all current file info
        $footprints = $this->getObjectFootprints($subPath);

        foreach ($footprints as $objectKey => $hash) {
            array_unshift($newObjects, new ChangedObject($objectKey, ChangedObject::CHANGE_TYPE_DELETE));
        }

        // Make a full path version of new objects for the version logic.
        $versionNewObjects = [];
        foreach ($newObjects as $newObject) {
            $versionNewObjects[] = new ChangedObject(($subPath ? trim($subPath, "/") . "/" : "") . $newObject->getObjectKey(),
                $newObject->getChangeType(), $newObject->getObjectContent(), $newObject->getLocalFilename(), $newObject->getMd5Hash());
        }

        $this->createVersion($versionNewObjects);
        parent::replaceAll($newObjects, $subPath);
    }

    /**
     * Apply the supplied changes
     *
     * @param ChangedObject[] $changedObjects
     */
    public function applyChanges($changedObjects) {
        $this->createVersion($changedObjects);
        parent::applyChanges($changedObjects);
    }


    /**
     * Get the current version for the system.
     */
    public function getCurrentVersion() {

        $version = 0;

        // Try and get the version file from current
        try {
            $versionObj = $this->storageProvider->getObject($this->containerKey, $this->path . "/" . self::VERSION_FILENAME);
            if ($versionObj) {
                $version = trim($versionObj->getContent());
            }
        } catch (ObjectDoesNotExistException $e) {
        }

        return $version;

    }


    /**
     * Get previous versions
     *
     * @return Version[]
     */
    public function getPreviousVersions() {
        try {
            $versions = $this->storageProvider->listObjects($this->containerKey, $this->basePath ? $this->basePath . "/versions" : "versions");
            $returnedVersions = [];
            foreach ($versions as $version) {

                $key = explode("/", $version->getKey());
                $versionNumber = array_pop($key);

                $returnedVersions[$versionNumber] = new Version($versionNumber, $version->getCreatedTime());
            }
            krsort($returnedVersions, SORT_NUMERIC);

            return array_values($returnedVersions);
        } catch (ObjectDoesNotExistException $e) {
            return [];
        }
    }


    /**
     * Get the changed files array required to go back to a previous version
     *
     * @param int $versionNumber
     *
     * @return VersionRestoreChangedObject[]
     */
    public function getChangesBackToPreviousVersion($targetVersionNumber) {

        $versions = $this->getPreviousVersions();

        $deleteObjects = [];
        $updateObjects = [];
        $versionNumber = -1;
        foreach ($versions as $version) {
            $versionNumber = $version->getVersion();
            $versionChanges = json_decode($this->storageProvider->getObject($this->containerKey, $this->basePath . "/versions/$versionNumber/" . self::CHANGES_FILENAME)->getContent(), true);

            foreach ($versionChanges as $objectKey => $changeType) {

                if ($changeType == "UPDATE" || $changeType == "RESTORE") {
                    $updateObjects[$objectKey] = new VersionRestoreChangedObject($objectKey, ChangedObject::CHANGE_TYPE_UPDATE, $versionNumber);
                    if (isset($deleteObjects[$objectKey]))
                        unset($deleteObjects[$objectKey]);
                }

                if ($changeType == "DELETE") {
                    $deleteObjects[$objectKey] = new VersionRestoreChangedObject($objectKey, ChangedObject::CHANGE_TYPE_DELETE);
                    if (isset($updateObjects[$objectKey]))
                        unset($updateObjects[$objectKey]);
                }

            }


            if ($versionNumber == $targetVersionNumber)
                break;
        }

        if ($versionNumber != $targetVersionNumber) {
            throw new VersionDoesNotExistException($targetVersionNumber);
        }


        return array_merge(array_values($updateObjects), array_values($deleteObjects));

    }


    /**
     * Revert back to a previous version
     *
     * @param int $versionNumber
     */
    public function revertToPreviousVersion($versionNumber) {

        // Version restore changes
        $versionRestoreChanges = $this->getChangesBackToPreviousVersion($versionNumber);

        /**
         * Now generate the changes as changed objects.
         */
        $changes = [];
        foreach ($versionRestoreChanges as $versionRestoreChange) {

            $objectKey = $versionRestoreChange->getObjectKey();

            if ($versionRestoreChange->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {

                $version = $versionRestoreChange->getVersion();
                $filename = $this->storageProvider->getFileSystemPath($this->containerKey, $this->basePath . "/versions/$version/$objectKey");

                $changes[] = new ChangedObject($objectKey, ChangedObject::CHANGE_TYPE_UPDATE, null, $filename);
            } else if ($versionRestoreChange->getChangeType() == ChangedObject::CHANGE_TYPE_DELETE) {
                $changes[] = new ChangedObject($objectKey, ChangedObject::CHANGE_TYPE_DELETE);
            }
        }

        // Apply the changes
        $this->applyChanges($changes);

    }


    /**
     * Create a version using a set of passed changes
     *
     * @param ChangedObject[] $changedObjects
     */
    private function createVersion($changedObjects) {


        // Get the current version
        $version = $this->getCurrentVersion();

        // Get set of footprints
        $footprints = $this->getObjectFootprints();

        // Now loop through and check for valid changes
        $changes = [];
        foreach ($changedObjects as $changedObject) {

            $hasFootprint = isset($footprints[$changedObject->getObjectKey()]);

            // Handle delete types
            if ($changedObject->getChangeType() == ChangedObject::CHANGE_TYPE_DELETE) {
                if (!$hasFootprint)
                    continue;

                $changes[$changedObject->getObjectKey()] = self::VERSION_OPERATION_RESTORE;
            }

            // Check updates
            if ($changedObject->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {
                $changes[$changedObject->getObjectKey()] = $hasFootprint ? self::VERSION_OPERATION_UPDATE : self::VERSION_OPERATION_DELETE;
            }
        }

        if (sizeof($changes) > 0) {

            // Create a versions folder and stash the versions file if version > 0
            if ($version > 0) {

                // Loop through each change
                $successfulChanges = $changes;

                $chunks = array_chunk($changes, ceil(sizeof($changes) / 10), true);


                foreach ($chunks as $chunkIndex => $chunkedChanges) {

                    $asyncFunctions[] = new AsynchronousFunction(function () use ($chunkedChanges, $chunkIndex, $successfulChanges, $version) {

                        foreach ($chunkedChanges as $objectKey => $versionOperation) {
                            if ($versionOperation != self::VERSION_OPERATION_DELETE) {
                                try {
                                    $this->storageProvider->copyObject($this->containerKey, $this->path . "/" . $objectKey, $this->basePath . "/versions/$version/" . $objectKey);
                                } catch (ObjectDoesNotExistException $e) {
                                    unset($successfulChanges[$objectKey]);
                                }
                            }
                        }

                    }, $this);


                }

                /**
                 * @var AsynchronousProcessor $asyncProcessor
                 */
                $asyncProcessor = Container::instance()->get(SynchronousProcessor::class);
                $asyncProcessor->executeAndWait($asyncFunctions);

                $this->storageProvider->saveObject($this->containerKey, $this->basePath . "/versions/$version/" . self::CHANGES_FILENAME,
                    json_encode($successfulChanges));


            }

            // Increment the version number and save it.
            $version++;
            $this->storageProvider->saveObject($this->containerKey, $this->path . "/" . self::VERSION_FILENAME, $version);

        }
    }


}
