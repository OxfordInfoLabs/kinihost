<?php


namespace Kinihost\Services\Storage\StorageProvider;


use Google\Cloud\Core\Exception\ConflictException;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Core\Iam\PolicyBuilder;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Storage\StorageClient;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use League\Flysystem\Exception;
use Kinihost\Exception\Storage\StorageProvider\InvalidFileStorageRootException;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary;

/**
 * Google cloud file storage provider.
 *
 * Class GoogleCloudStorageProvider
 *
 * @package Kinihost\Services\Storage\StorageProvider
 * @noProxy
 */
class GoogleCloudStorageProvider extends FileStorageProvider {

    /**
     * New storage client
     *
     * @var StorageClient
     */
    private $storage;


    // Construct with project id and key file path.
    public function __construct($projectId = null, $keyFilePath = null) {

        // Suppress container exceptions
        $this->suppressContainerExceptions = true;


        // Authenticate with google and get the storage service.
        $serviceBuilder = new ServiceBuilder([
            'keyFilePath' => $keyFilePath ?? Configuration::readParameter("google.keyfile.path")
        ]);

        $this->storage = $serviceBuilder->storage([
            "projectId" => $projectId ?? Configuration::readParameter("google.project.id")
        ]);


        // Register the stream wrapper
        $this->storage->registerStreamWrapper();

        try {
            parent::__construct("gs:/");
        } catch (InvalidFileStorageRootException $e) {
            // Fine
        }
    }

    /**
     * Create a bucket to use for access.
     *
     * @param string $containerKey
     * @param StorageProviderConfig $config
     */
    public function createContainer($containerKey, $config = null) {

        if (!$config) {
            $config = new StorageProviderConfig();
        }

        $region = Configuration::readParameter("google.bucket.region") ?? "europe-west2";

        $options = [
            "storageClass" => "REGIONAL",
            "location" => $region
        ];

        if ($config->isPublicAccess()) {
            $options["iamConfiguration"] = [
                "uniformBucketLevelAccess" => [
                    "enabled" => true
                ]
            ];

            $options["website"] = [
                "mainPageSuffix" => $config->getHostedSiteConfig()->getIndexPage(),
                "notFoundPage" => $config->getHostedSiteConfig()->getErrorPage()
            ];
        }


        try {

            // Create the bucket.
            $this->storage->createBucket($containerKey, $options);


            // Apply public access policy if required.
            if ($config->isPublicAccess()) {
                $bucket = $this->storage->bucket($containerKey);

                $policyBuilder = new PolicyBuilder();
                $policyBuilder->addBinding('roles/reader', ['allUsers']);
                $result = $policyBuilder->result();

                $bucket->iam()->setPolicy($result);
            }

        } catch (ConflictException $e) {
            // This is OK, we ignore it.
        }


    }

    /**
     * Update container with config.  For now, this is simply a mechanism to update the index and error pages for website hosting
     *
     * @param $containerKey
     * @param StorageProviderConfig $config
     * @return mixed|void
     */
    public function updateContainer($containerKey, $config) {

        $bucket = $this->storage->bucket($containerKey);

        if ($config->isPublicAccess()) {

            $options = $bucket->info();
            $options["website"]["mainPageSuffix"] = $config->getHostedSiteConfig()->getIndexPage();
            $options["website"]["notFoundPage"] = $config->getHostedSiteConfig()->getErrorPage();

            // Update the options.
            $bucket->update($options);

        }

    }


    /**
     * Remove the bucket
     *
     * @param $containerKey
     */
    public function removeContainer($containerKey) {
        $this->storage->bucket($containerKey)->delete();
    }


    /**
     * List all objects in bucket folder.
     *
     * @param $containerKey
     * @param string $objectKeyPrefix
     * @return \Kinihost\ValueObjects\Storage\StorageProvider\StoredObjectSummary[]
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     */
    public function listObjects($containerKey, $objectKeyPrefix = "") {

        $items = [];

        $objects = $this->storage->bucket($containerKey)->objects([
            "prefix" => $objectKeyPrefix ? $objectKeyPrefix . "/" : "",
            "delimiter" => "/"
        ]);


        // Loop through all items
        foreach ($objects as $item) {


            $info = $item->info();

            if (trim($info["name"], "/") == $objectKeyPrefix)
                continue;

            $items[$info["name"]] = new StoredObjectSummary($containerKey, $info["name"], $info["contentType"], $info["size"], \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $info["timeCreated"]), \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $info["updated"]));

        }


        foreach ($objects->prefixes() as $prefix) {
            $trimmedPrefix = trim($prefix, "/");

            if ($trimmedPrefix == trim($objectKeyPrefix, "/"))
                continue;


            $modifiedTime = \DateTime::createFromFormat("U", filemtime("gs://$containerKey/$prefix"));
            $createdTime = \DateTime::createFromFormat("U", filectime("gs://$containerKey/$prefix"));

            $items[$trimmedPrefix] = new StoredObjectSummary($containerKey, $trimmedPrefix, "folder", 0, $createdTime, $modifiedTime);
        }


        // Sort the items by filename
        ksort($items);

        return array_values($items);

    }


    /**
     * Override save to ensure that meta data is updated if required.
     *
     * @param $containerKey
     * @param $objectKey
     * @param $objectContent
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     */
    public function saveObject($containerKey, $objectKey, $objectContent) {
        parent::saveObject($containerKey, $objectKey, $objectContent);
        $this->updateMetaData($containerKey, $objectKey);
    }

    /**
     * Override save file to ensure that meta data is updated if required.
     *
     * @param $containerKey
     * @param $objectKey
     * @param $localFilename
     * @param null $md5Hash
     * @return string
     * @throws \Kinikit\Core\Exception\FileNotFoundException
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     */
    public function saveObjectFile($containerKey, $objectKey, $localFilename, $md5Hash = null) {
        $md5 = parent::saveObjectFile($containerKey, $objectKey, $localFilename, $md5Hash);
        $this->updateMetaData($containerKey, $objectKey);
        return $md5;
    }


    /**
     * Override copy to ensure that meta data is updated if required.
     *
     * @param string $containerKey
     * @param string $objectKey
     * @param string $newObjectKey
     * @throws \Kinihost\Exception\Storage\StorageProvider\ContainerDoesNotExistException
     * @throws \Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException
     */
    public function copyObject($containerKey, $objectKey, $newObjectKey) {
        parent::copyObject($containerKey, $objectKey, $newObjectKey);
        $this->updateMetaData($containerKey, $newObjectKey);
    }


    /**
     * Delete an object (handle folders as well)
     *
     * @param $containerKey
     * @param $objectKey
     */
    public function deleteObject($containerKey, $objectKey) {

        $fileRoot = "gs://$containerKey/$objectKey";


        if (file_exists($fileRoot)) {

            if (is_dir($fileRoot)) {
                $directory = opendir($fileRoot);
                while ($item = readdir($directory)) {
                    $subKey = $objectKey . "/" . $item;
                    $this->deleteObject($containerKey, $subKey);
                }
                rmdir($fileRoot);
            } else {
                // Delete
                unlink($fileRoot);

            }

        }


    }

    /**
     * Implement the get direct upload URL using google signed urls.
     *
     * @param string $objectPath
     * @return mixed
     */
    public function getDirectUploadURL($containerKey, $objectKey) {
        return $this->storage->bucket($containerKey)->object($objectKey)->beginSignedUploadSession();
    }

    /**
     * Get the direct download URL using google signed urls.
     *
     * @param $containerKey
     * @param $objectKey
     * @return mixed|string
     */
    public function getDirectDownloadURL($containerKey, $objectKey) {
        return $this->storage->bucket($containerKey)->object($objectKey)->signedUrl(new \DateTime('tomorrow'), [
            'method' => 'GET'
        ]);
    }


    /**
     * Update the meta data for a container
     *
     * @param $containerKey
     * @param $objectKey
     */
    private function updateMetaData($containerKey, $objectKey) {

        $objectKey = ltrim($objectKey, "/");
        $cacheAge = -1;

        if ($this->isCaching()) {
            // If HTML, we need to update the cache control.
            if (substr($objectKey, -3) == "htm" || substr($objectKey, -4) == "html") {
                $cacheAge = Configuration::readParameter("google.html.object.cache-age");
            } else if (substr($objectKey, -4) == "json" || $objectKey == ".oc-footprints") {
                $cacheAge = 0;
            }
        } else {
            $cacheAge = 0;
        }

        if ($cacheAge >= 0) {

            // Attempt to update meta data
            try {
                $this->storage->bucket($containerKey)->object($objectKey)->update(["cacheControl" => 'public,max-age=' . $cacheAge]);
            } catch (NotFoundException $e) {
                // Ignore not found exceptions
            }
        }
    }


}
