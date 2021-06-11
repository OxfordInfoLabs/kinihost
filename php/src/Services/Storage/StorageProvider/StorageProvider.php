<?php

namespace Kinihost\Services\Storage\StorageProvider;

use Kinikit\Core\Exception\FileNotFoundException;
use Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException;
use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObject;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;

/**
 * Interface for a storage provider for storing key / content data.  Designed for e.g. Amazon S3 / Google Cloud Storage
 *
 * Interface StorageProvider
 *
 * @implementation file Kinihost\Services\Storage\StorageProvider\FileStorageProvider
 * @implementation google Kinihost\Services\Storage\StorageProvider\GoogleCloudStorageProvider
 */
interface StorageProvider {


    /**
     * Create a storage container for the supplied container key
     *
     * @param $containerKey
     * @param StorageProviderConfig $config
     */
    public function createContainer($containerKey, $config = null);


    /**
     * Update a container with a new storage config.
     *
     * @param $containerKey
     * @param StorageProviderConfig $config
     *
     * @return mixed
     */
    public function updateContainer($containerKey, $config);


    /**
     * Remove the storage container for the supplied container key
     *
     * @param $containerKey
     * @throws ContainerDoesNotExistException
     */
    public function removeContainer($containerKey);


    /**
     * List objects using the supplied key prefix for e.g. folder style listings.
     *
     * @param $containerKey
     * @param string $objectKeyPrefix
     *
     * @return StoredObjectSummary[]
     *
     * @throws ContainerDoesNotExistException
     */
    public function listObjects($containerKey, $objectKeyPrefix = "");


    /**
     * Get direct upload URL for an object if the storage engine supports it.
     * Otherwise, this should return null.
     *
     * @param string $objectPath
     * @return mixed
     */
    public function getDirectUploadURL($containerKey, $objectKey);


    /**
     * Get a direct download URL for an object if the storage engine supports it.
     *
     * @param $containerKey
     * @param $objectKey
     * @return mixed
     */
    public function getDirectDownloadURL($containerKey, $objectKey);


    /**
     * Get the file system path if the storage engine supports it.
     *
     * @param $containerKey
     * @param $objectKey
     * @return mixed
     */
    public function getFileSystemPath($containerKey, $objectKey);

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
    public function getObject($containerKey, $objectKey);


    /**
     * Get the contents of an object as a string - much faster where
     * other data is not required for an object
     *
     * @param $containerKey
     * @param $objectKey
     *
     * @return string
     * @throws ObjectDoesNotExistException
     *
     * @throws ContainerDoesNotExistException
     */
    public function getObjectContents($containerKey, $objectKey);


    /**
     * Save a new object using the supplied container, object key and content
     *
     * @param $containerKey
     * @param $objectKey
     * @param $objectContent
     *
     * @throws ContainerDoesNotExistException
     */
    public function saveObject($containerKey, $objectKey, $objectContent);


    /**
     * Save a new object from a local file - should return an md5 footprint for the file.
     *
     * @param $containerKey
     * @param $objectKey
     * @param $localFilename
     *
     * @param null $md5Hash
     * @return string
     *
     * @throws FileNotFoundException
     * @throws ContainerDoesNotExistException
     */
    public function saveObjectFile($containerKey, $objectKey, $localFilename, $md5Hash = null);


    /**
     * Remove an object using the supplied container key and object key.
     *
     * @param $containerKey
     * @param $objectKey
     *
     * @throws ContainerDoesNotExistException
     * @throws ObjectDoesNotExistException
     */
    public function deleteObject($containerKey, $objectKey);


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
    public function copyObject($containerKey, $objectKey, $newObjectKey);

    /**
     * @param bool $caching
     */
    public function setCaching($caching);

    /**
     * @return bool
     */
    public function isCaching();


}
