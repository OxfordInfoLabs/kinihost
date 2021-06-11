<?php

namespace Kinihost\Services\Storage;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Kinikit\Core\Asynchronous\AsynchronousFunction;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Asynchronous\Processor\SynchronousProcessor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\Services\Storage\StorageProvider\StorageProvider;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\ValueObjects\Storage\ChangeResult;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;


/**
 * Abstract store implementation
 *
 *
 */
class StorageRoot {

    /**
     * The storage provider
     *
     * @var StorageProvider
     */
    protected $storageProvider;

    /**
     * The container key for which we are rooting
     *
     * @var string
     */
    protected $containerKey;


    /**
     * The path to this storage root inside the container.
     *
     * @var string
     */
    protected $path;


    // Footprint filename
    const FOOTPRINT_FILENAME = ".oc-footprints";


    /**
     * StorageRoot constructor.
     *
     * @param string $storageProviderKey
     * @param string $containerKey
     * @param string $path
     */
    public function __construct($storageProviderKey, $containerKey, $path = "") {
        try {
            $this->storageProvider = $storageProviderKey instanceof StorageProvider ? $storageProviderKey : Container::instance()->getInterfaceImplementation(StorageProvider::class, $storageProviderKey);
        } catch (MissingInterfaceImplementationException $e) {
            $this->storageProvider = null;
        }
        $this->containerKey = $containerKey;
        $this->path = $path;
    }

    /**
     * Get the storage provider
     *
     * @return StorageProvider
     */
    public function getStorageProvider() {
        return $this->storageProvider;
    }

    /**
     * Get the container key.
     *
     * @return string
     */
    public function getContainerKey() {
        return $this->containerKey;
    }


    /**
     * Get the path into the container
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Ensure the storage root is created
     *
     * @param StorageProviderConfig $storageConfig
     */
    public function create($storageConfig = null) {
        $this->storageProvider->createContainer($this->containerKey, $storageConfig);
    }


    /**
     * Update with a new storage config
     *
     * @param StorageProviderConfig $storageConfig
     */
    public function update($storageConfig) {
        $this->storageProvider->updateContainer($this->containerKey, $storageConfig);
    }


    /**
     * Remove this storage root or the sub path if it is not rooted at /
     */
    public function remove() {

        if ($this->path == "") {

            // Empty the container first
            $this->replaceAll([]);

            // Remove the footprint file
            $this->storageProvider->deleteObject($this->containerKey, $this->getFullObjectPath(self::FOOTPRINT_FILENAME));

            // Remove the container provided this is a top level
            $this->storageProvider->removeContainer($this->containerKey);
        } else {
            $this->storageProvider->deleteObject($this->containerKey, $this->path);
        }
    }


    /**
     * List the stored object items for the sub path (defaults to root)
     *
     * @param string $subPath
     * @return StoredObjectSummary[]
     */
    public function listObjects($subPath = "") {

        $list = $this->storageProvider->listObjects($this->containerKey, $this->getFullObjectPath($subPath));

        // Ensure we exclude footprint files from lists.
        $newObjects = [];
        foreach ($list as $item) {
            if ($this->path) {
                $key = substr($item->getKey(), strlen($this->path) + 1);
            } else {
                $key = $item->getKey();
            }

            if (substr($key, 0, 3) != ".oc") {
                $newObjects[] = new StoredObjectSummary($item->getContainerKey(), $key, $item->getContentType(), $item->getSize(), $item->getCreatedTime(), $item->getLastModifiedTime());
            }

        }
        return $newObjects;
    }

    /**
     * Get a stored object from the object path (below the storage root).
     *
     * @param string $objectPath
     * @return StoredObject
     */
    public function getObject($objectPath) {
        $object = $this->storageProvider->getObject($this->containerKey, $this->getFullObjectPath($objectPath));

        if ($this->path) {
            $key = substr($object->getKey(), strlen($this->path) + 1);
        } else {
            $key = $object->getKey();
        }

        return new StoredObject($this->containerKey, $key, $object->getContentType(), $object->getSize(), $object->getCreatedTime(), $object->getLastModifiedTime(), $object->getContent());
    }


    /**
     * Get the object contents - this is a more efficient call than above which
     * should be implemented by the storage provider to return the contents of
     * an object if no other data is requird.
     *
     * @param $objectPath
     */
    public function getObjectContents($objectPath) {
        return $this->storageProvider->getObjectContents($this->containerKey, $this->getFullObjectPath($objectPath));
    }


    /**
     * Get the local file path if supported by the storage engine.
     *
     * @param $objectPath
     * @return mixed
     */
    public function getLocalFilePath($objectPath) {
        return $this->storageProvider->getFileSystemPath($this->containerKey, $this->getFullObjectPath($objectPath));
    }


    /**
     * Get a direct upload URL if supported by the storage engine.
     *
     * @param string $objectPath
     * @return string
     */
    public function getDirectUploadURL($objectPath) {
        return $this->storageProvider->getDirectUploadURL($this->containerKey, $this->getFullObjectPath($objectPath));
    }


    /**
     * Get a direct download URL if supported by the storage engine.
     *
     * @param string $objectPath
     * @return string
     */
    public function getDirectDownloadURL($objectPath) {
        return $this->storageProvider->getDirectDownloadURL($this->containerKey, $this->getFullObjectPath($objectPath));
    }


    /**
     * Save an object using the object path (below the storage root) and explicit content.
     *
     * @param string $objectPath
     * @param string $objectContent
     */
    public function saveObject($objectPath, $objectContent) {
        $this->storageProvider->saveObject($this->containerKey, $this->getFullObjectPath($objectPath), $objectContent);
        $this->updateFootprintFile($objectPath, md5($objectContent));
    }


    /**
     * Save an object using the object path (below the storage root) and a local file identified by path.
     *
     * @param string $objectPath
     * @param string $localFilePath
     */
    public function saveObjectFile($objectPath, $localFilePath) {
        $footprint = $this->storageProvider->saveObjectFile($this->containerKey, $this->getFullObjectPath($objectPath), $localFilePath);
        $this->updateFootprintFile($objectPath, $footprint);
    }


    /**
     * Delete an object using the object path (below the storage root).
     *
     * @param string $objectPath
     */
    public function deleteObject($objectPath) {
        $this->storageProvider->deleteObject($this->containerKey, $this->getFullObjectPath($objectPath));
        $this->removeFromFootprintFile($objectPath);
    }


    /**
     * Replace all items in this storage root with the array of new objects passed in.
     * These should all be of type UPDATE (DELETES will be ignored).
     *
     * If a subPath is supplied, the replace will occur for all files below that path
     * and all new objects will be assumed to be pathed relative to the sub path.
     *
     * @param ChangedObject[] $newObjects
     */
    public function replaceAll($newObjects, $subPath = "") {

        // Gather sub footprints and all footprints if necessary
        $subFootprints = $this->getObjectFootprints($subPath);
        $allFootPrints = $subPath ? $this->getObjectFootprints() : $subFootprints;

        $saveObjects = [];

        foreach ($newObjects as $changedObject) {
            if ($changedObject->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {

                $fullKey = ($subPath ? trim($subPath, "/") . "/" : "") . $changedObject->getObjectKey();

                if (isset($subFootprints[$changedObject->getObjectKey()])) {
                    unset($subFootprints[$changedObject->getObjectKey()]);
                }
                $changedObject->setObjectKey($fullKey);
                $saveObjects[] = $changedObject;
            }
        }


        // Remove the old objects directly from the storage provider.
        foreach ($subFootprints as $key => $footprint) {
            $key = ($subPath ? trim($subPath, "/") . "/" : "") . $key;
            $this->storageProvider->deleteObject($this->containerKey, $this->getFullObjectPath($key));
            unset($allFootPrints[$key]);
        }


        // Now apply changes as usual to the save objects
        $this->doApplyChanges($saveObjects, $allFootPrints);

    }


    /**
     * Synchronise this storage root with another one.  Completely replaces the
     * contents of this storage root recursively with the other root.
     *
     * If mySubPath is set, the replace will happen at the specified sub path otherwise
     * the whole root will be replaced.
     *
     * If otherRootSubPath is set only files at the subpath of the other root will be copied.
     * These will be assumed to be moved to the top level of this storage root unless mySubPath
     * is also supplied
     *
     * @param StorageRoot $otherStorageRoot
     * @param string $mySubPath
     * @param string $otherRootSubPath
     */
    public function synchronise($otherStorageRoot, $mySubPath = "", $otherRootSubPath = "") {

        $otherRootFootprints = $otherStorageRoot->getObjectFootprints($otherRootSubPath);

        // Loop through footprints in other root and create changes
        $changes = [];
        foreach ($otherRootFootprints as $filename => $footprint) {
            $targetPath = ($otherRootSubPath ? $otherRootSubPath . "/" : "") . $filename;
            $targetFilename = $otherStorageRoot->getLocalFilePath($targetPath);
            $changes[] = new ChangedObject($filename, ChangedObject::CHANGE_TYPE_UPDATE, null,
                $targetFilename, $footprint);
        }


        // Replace all objects
        $this->replaceAll($changes, $mySubPath);


    }


    /**
     * Apply the set of changes supplied as ChangedObject[] items.  This can be a mixture of
     * saves and deletes.  If MD5 hashes are supplied for new items these will be optimised where possible.
     *
     * @param ChangedObject[] $changedObjects
     */
    public function applyChanges($changedObjects) {
        return $this->doApplyChanges($changedObjects);
    }


    /**
     * Get all object footprints.  This defaults to all objects from the root of container
     * but if a sub path is passed, only object footprints matching this subpath will be returned
     * and the path prefix will be trimmed from the keys for convenience.
     */
    public function getObjectFootprints($subPath = "") {

        $footprints = [];
        try {

            $footprintObject = $this->storageProvider->getObject($this->containerKey, $this->getFullObjectPath(self::FOOTPRINT_FILENAME));


            if ($footprintObject)
                $footprints = json_decode($footprintObject->getContent(), true);

            if ($subPath) {
                $newFootprints = [];
                foreach ($footprints as $key => $hash) {
                    if (substr($key, 0, strlen($subPath)) == $subPath) {
                        $newFootprints[ltrim(substr($key, strlen($subPath)), "/")] = $hash;
                    }
                }
                $footprints = $newFootprints;
            }

        } catch (ObjectDoesNotExistException $e) {
            // Ignore and return blank.
        }

        return $footprints;
    }


    /**
     * @param $changedObjects
     * @return ChangeResult
     * @throws \Kinikit\Core\Exception\FileNotFoundException
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     */
    private function doApplyChanges($changedObjects, $footprints = null) {

        // Get object footprints for optimised saving
        $originalFootprints = $footprints ?? $this->getObjectFootprints();
        $objectFootprints = $originalFootprints;

        $created = [];
        $updated = [];
        $deleted = [];
        $failed = [];

        // Chunked changes
        if (sizeof($changedObjects) > 0)
            $chunkedChanges = array_chunk($changedObjects, ceil(sizeof($changedObjects) / 10), false);
        else
            $chunkedChanges = [];

        // Loop through and process accordingly
        $asyncFunctions = [];

        foreach ($chunkedChanges as $chunkIndex => $changesChunk) {

            $asyncFunctions[] = new AsynchronousFunction(function () use ($chunkIndex, $changesChunk, $originalFootprints) {

                $created = [];
                $updated = [];
                $deleted = [];
                $failed = [];

                foreach ($changesChunk as $newObject) {

                    // Handle deletes as these are straightforward
                    $objectKey = $newObject->getObjectKey();
                    $objectPath = $this->getFullObjectPath($objectKey);


                    if ($newObject->getChangeType() == ChangedObject::CHANGE_TYPE_DELETE) {
                        try {
                            $this->storageProvider->deleteObject($this->containerKey, $objectPath);
                            $deleted[] = $objectKey;
                        } catch (ObjectDoesNotExistException $e) {
                            $failed[$objectKey] = ChangeResult::FAILED_DELETE_NOT_FOUND;
                        }
                    }


                    // Handle updates
                    if ($newObject->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {

                        $originalHash = $originalFootprints[$objectKey] ?? null;

                        // Optimise if hash matches current hash.
                        if ($newObject->getMd5Hash() && ($originalHash == $newObject->getMd5Hash())) {
                            continue;
                        }

                        $footprint = $originalFootprints[$objectKey] ?? null;
                        if ($newObject->getObjectContent()) {
                            $this->storageProvider->saveObject($this->containerKey, $objectPath, $newObject->getObjectContent());
                            $footprint = md5($newObject->getObjectContent());
                        } else if ($newObject->getLocalFilename()) {
                            $footprint = $this->storageProvider->saveObjectFile($this->containerKey, $objectPath, $newObject->getLocalFilename(), $newObject->getMd5Hash());
                        }

                        // Either set updated or created according to pre-existence
                        if (isset($originalFootprints[$objectKey]))
                            $updated[$objectKey] = $footprint;
                        else
                            $created[$objectKey] = $footprint;

                    }

                }

                return ["created" => $created, "updated" => $updated, "deleted" => $deleted, "failed" => $failed];

            }, $this);


        }

        // Set to Synchronous processing for now - seems quicker in this instance.
        $asyncProcessor = Container::instance()->get(SynchronousProcessor::class);
        $asyncProcessor->executeAndWait($asyncFunctions);

        // update our arrays
        foreach ($asyncFunctions as $asyncFunction) {

            if (is_array($asyncFunction->getReturnValue())) {
                $metrics = $asyncFunction->getReturnValue();

                // Ensure the footprints are updated
                $objectFootprints = array_merge($objectFootprints, $metrics["created"]);
                $objectFootprints = array_merge($objectFootprints, $metrics["updated"]);

                // Update the changed items for return
                $created = array_merge($created, array_keys($metrics["created"]));
                $updated = array_merge($updated, array_keys($metrics["updated"]));


                // Remove deleted items from footprints
                if (sizeof($metrics["deleted"])) {
                    $deleted = array_merge($deleted, $metrics["deleted"]);
                    foreach ($metrics["deleted"] as $deletedKey) {
                        unset($objectFootprints[$deletedKey]);
                    }
                }

                $failed = array_merge($failed, $metrics["failed"]);

            } else {
                print_r($asyncFunction->getExceptionData());
            }
        }

        // Save the footprints.
        $this->saveFootprintFile($objectFootprints);


        return new ChangeResult($created, $updated, $deleted, $failed);
    }


    // Update the footprint file
    private function updateFootprintFile($objectPath, $contentMD5) {

        $footprints = $this->getObjectFootprints();

        $footprints[$objectPath] = $contentMD5;

        $this->saveFootprintFile($footprints);
    }


    // Remove from footprint file
    private function removeFromFootprintFile($objectPath) {

        $footprints = $this->getObjectFootprints();

        foreach ($footprints as $key => $value) {
            if (substr($key, 0, strlen($objectPath)) == $objectPath) {
                unset($footprints[$key]);
            }
        }

        $this->saveFootprintFile($footprints);


    }


    /**
     * Save the footprint file using the supplied footprints.
     *
     * @param array $footprints
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     */
    private function saveFootprintFile($footprints) {
        $this->storageProvider->saveObject($this->containerKey, $this->getFullObjectPath(self::FOOTPRINT_FILENAME), json_encode($footprints));
    }


    // Get the full object path
    protected function getFullObjectPath($objectPath) {
        $newObjectPath = "";
        if ($this->path) {
            $newObjectPath = $this->path;
        }

        if ($objectPath) {
            if ($newObjectPath) $newObjectPath .= "/";
            $newObjectPath .= $objectPath;
        }

        return $newObjectPath;

    }


}
