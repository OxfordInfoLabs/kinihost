<?php


namespace Kinihost\Services\Site;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;

use Kinihost\Exception\Storage\StorageProvider\ObjectDoesNotExistException;

use Kinihost\Services\Storage\VersionedStorageRoot;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteConfig;
use Kinihost\Services\Build\BuildService;
use Kinihost\ValueObjects\Build\SourceUploadBuild;

/**
 * Manage functions connected with the source for this site.
 *
 * Class SiteSourceService
 * @package Kinihost\Services\Source
 */
class SiteSourceService {

    /**
     * @var SiteStorageManager
     */
    private $siteStorageManager;


    /**
     * SourceService constructor.
     *
     * @param SiteStorageManager $siteStorageManager
     */
    public function __construct($siteStorageManager) {
        $this->siteStorageManager = $siteStorageManager;
    }


    /**
     * Install blank content into the passed site.
     */
    public function installBlankContent($site) {

        $blankSiteRoot = $this->getBlankSiteRoot();
        $blankSiteObjects = $blankSiteRoot->getObjectFootprints();

        $changedObjects = [];
        foreach ($blankSiteObjects as $filename => $footprint) {
            $fileRoot = $blankSiteRoot->getStorageProvider()->getFileSystemPath($blankSiteRoot->getContainerKey() . "/" . $blankSiteRoot->getPath(), $filename);
            $changedObjects[] = new ChangedObject("source/" . $filename, ChangedObject::CHANGE_TYPE_UPDATE, null, $fileRoot, $footprint);
        }

        // Replace all content with the theme contents.
        $this->siteStorageManager->getContentRoot($site)->replaceAll($changedObjects);

    }


    /**
     * Initialise production content, using the blank site content.
     *
     * @param $site
     */
    public function initialiseProductionContent($site) {

        $blankSiteRoot = $this->getBlankSiteRoot();
        $blankSiteObjects = $blankSiteRoot->getObjectFootprints();

        $changedObjects = [];
        foreach ($blankSiteObjects as $filename => $footprint) {
            $fileRoot = $blankSiteRoot->getStorageProvider()->getFileSystemPath($blankSiteRoot->getContainerKey() . "/" . $blankSiteRoot->getPath(), $filename);
            $changedObjects[] = new ChangedObject($filename, ChangedObject::CHANGE_TYPE_UPDATE, null, $fileRoot, $footprint);
        }

        // Replace all content with the theme contents.
        $this->siteStorageManager->getProductionRoot($site)->replaceAll($changedObjects);

    }


    /**
     * Get the object footprints
     */
    public function getCurrentSourceObjectFootprints($siteKey) {

        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->getSiteByKey($siteKey);

        // Get the object footprints for the source code.
        return $this->siteStorageManager->getContentRoot($site)->getObjectFootprints("source");
    }


    /**
     * Get the set of files to deploy as change objects with local file references.
     *
     * @param Site $site
     */
    public function getCurrentDeploymentChangedFiles($site) {

        // Grab the content root and associated storage provider.
        $contentRoot = $this->siteStorageManager->getContentRoot($site);
        $contentStorageProvider = $contentRoot->getStorageProvider();

        $allObjects = $contentRoot->getObjectFootprints("source");

        $publishDirectory = ($site->getConfig()->getPublishDirectory() ?? "") == "." ? "" : $site->getConfig()->getPublishDirectory();

        $changedObjects = [];
        foreach ($allObjects as $key => $md5) {
            if (substr($key, 0, strlen($publishDirectory)) == $publishDirectory) {
                $filename = $contentStorageProvider->getFileSystemPath($contentRoot->getContainerKey(), $contentRoot->getPath() . "/source/" . $key);
                if ($publishDirectory) $key = substr($key, strlen($publishDirectory) + 1);
                $changedObjects[$key] = new ChangedObject($key, ChangedObject::CHANGE_TYPE_UPDATE, null, $filename, $md5);
            }
        }
        return $changedObjects;
    }


    /**
     * Create a source upload build for a set of changed objects.
     *
     * @param string $siteKey
     * @param ChangedObject[] $changedObjects
     *
     * @return SourceUploadBuild
     */
    public function createSourceUploadBuild($siteKey, $changedObjects) {


        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->getSiteByKey($siteKey);

        $buildService = Container::instance()->get(BuildService::class);
        $build = $buildService->createBuild($siteKey, Build::TYPE_SOURCE_UPLOAD, Build::STATUS_PENDING, ["changedObjects" => $changedObjects]);

        $uploadRoot = $this->siteStorageManager->getUploadRoot($site);

        $buildId = $build->getId();


        // Create signed upload urls for each object
        $uploadUrls = [];
        foreach ($changedObjects as $object) {
            if ($object->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {
                $key = $object->getObjectKey();
                $uploadUrls[$key] = $uploadRoot->getDirectUploadURL($buildId . "/" . $key);
            }
        }


        return new SourceUploadBuild($build, $uploadUrls);
    }


    /**
     * Apply source changes from changed objects - these are assumed to have been already
     * uploaded to the upload paths.
     *
     * @param $buildId
     * @param ChangedObject[] $changedObjects
     * @param Site $site
     */
    public function applyUploadedSource($buildId, $changedObjects, $site) {

        // Gather upload root and update location of changed files.
        $uploadRoot = $this->siteStorageManager->getUploadRoot($site);
        $uploadStorageProvider = $uploadRoot->getStorageProvider();

        /**
         * Loop through each changed object and update the local file path.
         */
        foreach ($changedObjects as $changedObject) {

            // Construct the local filename for files being copied.
            if ($changedObject->getChangeType() == ChangedObject::CHANGE_TYPE_UPDATE) {
                $localFilename = $uploadStorageProvider->getFileSystemPath($uploadRoot->getContainerKey(), $uploadRoot->getPath() . "/" . $buildId . "/" . $changedObject->getObjectKey());
                $changedObject->setLocalFilename($localFilename);
            }
            $changedObject->setObjectKey("source/" . $changedObject->getObjectKey());
        }


        // Now pass these as changes to the content root.
        $contentRoot = $this->siteStorageManager->getContentRoot($site);
        $contentRoot->applyChanges($changedObjects);

        // Finally delete the build upload folder when all is well.
        $uploadRoot->deleteObject($buildId);

    }


    /**
     * Get an array of download URLs for the supplied object keys which are assumed to be
     * relative to the source directory for the given site.
     *
     * The return value is an array of download URLs indexed by the original key passed in.
     *
     * @param string $siteKey
     * @param string[] $objectKeys
     *
     * @return string[string]
     */
    public function createSourceDownloadURLs($siteKey, $objectKeys) {


        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->getSiteByKey($siteKey);

        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        $downloadUrls = [];
        foreach ($objectKeys as $objectKey) {
            $downloadUrls[$objectKey] = $contentRoot->getDirectDownloadURL("source/" . $objectKey);
        }

        return $downloadUrls;

    }


    /**
     * List current source for the site, optionally limiting by sub folder.
     *
     * @param string $siteKey
     * @param string $subFolder
     */
    public function listCurrentSourceForSite($siteKey, $subFolder = "") {

        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->getSiteByKey($siteKey);

        return $this->siteStorageManager->getContentRoot($site)->listObjects("source" . ($subFolder ? "/$subFolder" : ""));

    }


    /**
     * Read the site config from the current source tree.
     *
     * @return SiteConfig
     */
    public function getCurrentSiteConfig($siteKey) {

        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->getSiteByKey($siteKey);

        // Get the content root
        $contentRoot = $this->siteStorageManager->getContentRoot($site);

        try {
            $data = $contentRoot->getObject("source/.kinihost-deploy");
            $content = json_decode($data->getContent(), true);
            if (is_array($content)) {
                $objectBinder = Container::instance()->get(ObjectBinder::class);
                return $objectBinder->bindFromArray($content, SiteConfig::class, false);
            } else {
                return new SiteConfig();
            }
        } catch (ObjectDoesNotExistException $e) {
            return new SiteConfig();
        }
    }


    /**
     * Get the storage root for the blank site content
     *
     * @param $themeKey
     * @return VersionedStorageRoot
     */
    public function getBlankSiteRoot() {

        $storageProvider = Configuration::readParameter("kinihost.storage.provider");
        $serviceDomain = "themes." . Configuration::readParameter("kinihost.service.domain");
        $subPath = "global/default";

        return new VersionedStorageRoot($storageProvider, $serviceDomain, $subPath);

    }


}
