<?php

namespace Kinihost\Services\Storage\StorageProvider;


use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\FileNotFoundException;
use Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException;
use Kinihost\Exception\Storage\StorageProvider\InvalidFileStorageRootException;
use Kinihost\Exception\Storage\StorageProvider\MissingFileStorageRootException;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\Services\Storage\StorageRoot;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;

/**
 * Simple file storage provider - stores files to the file system.
 *
 * Class FileStorageProvider
 */
class FileStorageProvider implements StorageProvider {

    /**
     * @var string
     */
    private $fileRoot;

    /**
     * @var bool
     */
    private $caching = true;

    /**
     * Suppress container exceptions if necessary
     *
     * @var bool
     */
    protected $suppressContainerExceptions = false;


    /**
     * Constructor for the file storage provider
     *
     * FileStorageProvider constructor.
     */
    public function __construct($fileRoot = null) {
        $this->fileRoot = $fileRoot ? $fileRoot : Configuration::readParameter("file.storage.root");
        if (!$this->fileRoot)
            throw new MissingFileStorageRootException();

        if (!file_exists($this->fileRoot))
            throw new InvalidFileStorageRootException($this->fileRoot);

    }

    /**
     * Get the file root for this storage provider.
     *
     * @return string
     */
    public function getFileRoot() {
        return $this->fileRoot;
    }


    /**
     * Create a storage container for the supplied container key
     *
     * @param $containerKey
     * @param bool $publicAccess
     */
    public function createContainer($containerKey, $config = null) {
        if (!file_exists($this->fileRoot . "/" . $containerKey)) {
            mkdir($this->fileRoot . "/" . $containerKey, 0777);
        }
    }


    /**
     * Update a container with a new storage config - nothing to do here
     *
     * @param $containerKey
     * @param StorageProviderConfig $config
     *
     * @return mixed
     */
    public function updateContainer($containerKey, $config) {

    }


    /**
     * Remove the storage container for the supplied container key
     *
     * @param $containerKey
     * @throws ContainerDoesNotExistException
     */
    public function removeContainer($containerKey) {
        $containerRoot = $this->getContainerRoot($containerKey);
        passthru("rm -rf " . $containerRoot);
    }


    /**
     * List objects using the supplied key prefix for e.g. folder style listings.
     *
     * @param $containerKey
     * @param string $objectKeyPrefix
     * @return StoredObjectSummary[]
     *
     * @throws ContainerDoesNotExistException
     */
    public function listObjects($containerKey, $objectKeyPrefix = "") {
        $directoryRoot = $this->getObjectPath($containerKey, $objectKeyPrefix);
        $iterator = new \DirectoryIterator($directoryRoot);
        $items = [];
        $prefix = $objectKeyPrefix ? $objectKeyPrefix . "/" : "";
        foreach ($iterator as $item) {

            if ($item->isDot())
                continue;


            $modifiedTime = \DateTime::createFromFormat("U", $item->getMTime());
            $createdTime = \DateTime::createFromFormat("U", $item->getCTime());


            if ($item->isDir()) {
                $items[$item->getFilename()] = new StoredObjectSummary($containerKey, $prefix . $item->getFilename(), "folder", 0, $createdTime, $modifiedTime);
            } else {
                $items[$item->getFilename()] = new StoredObjectSummary($containerKey, $prefix . $item->getFilename(), mime_content_type($item->getRealPath()), $item->getSize(), $createdTime, $modifiedTime);
            }
        }

        // Sort the items by filename
        sort($items);

        return array_values($items);
    }


    /**
     * Get direct upload URL for an object if the storage engine supports it.
     * Otherwise, this should return null.
     *
     * @param string $objectPath
     * @return mixed
     */
    public function getDirectUploadURL($containerKey, $objectKey) {
        return "/upload/$containerKey/$objectKey";
    }


    /**
     * Get a direct download URL for an object if the storage engine supports it.
     *
     * @param $containerKey
     * @param $objectKey
     * @return mixed
     */
    public function getDirectDownloadURL($containerKey, $objectKey) {
        return "/download/$containerKey/$objectKey";
    }


    /**
     * Get the local server filesystem path for an object.  This is useful when files are being copied between
     * buckets etc.
     *
     * @param $containerKey
     * @param $objectKey
     */
    public function getFileSystemPath($containerKey, $objectKey) {
        return $this->fileRoot . "/$containerKey/$objectKey";
    }


    /**
     * Get an object
     *
     * @param $containerKey
     * @param $objectKey
     *
     * @return StoredObject
     *
     * @throws ContainerDoesNotExistException
     * @throws ObjectDoesNotExistException
     */
    public function getObject($containerKey, $objectKey) {
        $objectPath = $this->getObjectPath($containerKey, $objectKey);

        $createdTime = \DateTime::createFromFormat("U", filectime($objectPath));
        $modifiedTime = \DateTime::createFromFormat("U", filemtime($objectPath));


        return new StoredObject($containerKey, $objectKey, mime_content_type($objectPath), filesize($objectPath), $createdTime, $modifiedTime, file_get_contents($objectPath));
    }

    /**
     * Get obejct contents
     *
     * @param $containerKey
     * @param $objectKey
     * @return string|void
     */
    public function getObjectContents($containerKey, $objectKey) {

        // Get object path with checks
        $objectPath = $this->getObjectPath($containerKey, $objectKey);

        // Return the contents directly
        return file_get_contents($objectPath);

    }


    /**
     * Save a new object using the supplied container, object key and content
     *
     * @param $containerKey
     * @param $objectKey
     * @param $objectContent
     *
     * @throws ContainerDoesNotExistException
     */
    public function saveObject($containerKey, $objectKey, $objectContent) {
        $objectPath = $this->getObjectPath($containerKey, $objectKey, true);
        file_put_contents($objectPath, $objectContent);
    }


    /**
     * Save a new object from a local file
     *
     * @param $containerKey
     * @param $objectKey
     * @param $localFilename
     *
     * @param null $md5Hash
     * @return string
     *
     * @throws ContainerDoesNotExistException
     * @throws FileNotFoundException
     */
    public function saveObjectFile($containerKey, $objectKey, $localFilename, $md5Hash = null) {
        $objectPath = $this->getObjectPath($containerKey, $objectKey, true);
        try {
            copy($localFilename, $objectPath);
        } catch (\ErrorException $e) {
            // Ignore for now.
        }
        return $md5Hash ? $md5Hash : md5_file($localFilename);
    }


    /**
     * Remove an object using the supplied container key and object key.
     *
     * @param $containerKey
     * @param $objectKey
     *
     * @throws ContainerDoesNotExistException
     * @throws ObjectDoesNotExistException
     */
    public function deleteObject($containerKey, $objectKey) {
        $objectPath = $this->getObjectPath($containerKey, $objectKey);

        if (is_dir($objectPath)) {
            $objects = scandir($objectPath);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $this->deleteObject($containerKey, $objectKey . "/" . $object);
                }
            }
            rmdir($objectPath);
        } else {
            unlink($objectPath);
        }
    }


    /**
     * Copy an object locally within the same container
     *
     * @param string $containerKey
     * @param string $objectKey
     * @param string $newObjectKey
     *
     * @throws ContainerDoesNotExistException
     * @throws ObjectDoesNotExistException
     */
    public function copyObject($containerKey, $objectKey, $newObjectKey) {
        $objectPath = $this->getObjectPath($containerKey, $objectKey);
        $newObjectPath = $this->getObjectPath($containerKey, $newObjectKey, true);
        copy($objectPath, $newObjectPath);
    }


    // Get container root, throw if none existent.
    private function getContainerRoot($containerKey) {
        if ($this->suppressContainerExceptions || file_exists($this->fileRoot . "/" . $containerKey)) {
            return $this->fileRoot . "/" . $containerKey;
        } else {
            throw new ContainerDoesNotExistException($containerKey);
        }
    }

    // Get object folder and filename
    protected function getObjectPath($containerKey, $objectKey, $create = false) {

        $containerRoot = $this->getContainerRoot($containerKey);

        $exploded = explode("/", $objectKey);
        $filename = array_pop($exploded);

        $directory = $containerRoot;
        if (sizeof($exploded)) {
            $directory .= "/" . join("/", $exploded);

            if ($create && !is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }


        if (!$create && !file_exists($directory . "/" . $filename))
            throw new ObjectDoesNotExistException($containerKey, $objectKey);

        return $directory . "/" . $filename;
    }

    public function setCaching($caching) {
        $this->caching = $caching;
    }

    public function isCaching() {
        return $this->caching;
    }


}
