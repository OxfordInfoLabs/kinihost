<?php


namespace Kinihost\Services\Build\Runner;


use Kinikit\Core\Binding\ObjectBinder;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;

use Kinihost\Services\Content\ContentService;
use Kinihost\Services\Content\DeploymentProcessors\BootstrapDataProcessor;
use Kinihost\Services\Content\DeploymentProcessors\ContentDeploymentProcessor;
use Kinihost\Services\Content\DeploymentProcessors\MetaDataDeploymentProcessor;
use Kinihost\Services\Content\EntityDefinitionService;
use Kinihost\Services\Site\SiteDataUpdateManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;
use Kinihost\Services\Source\DeploymentProcessors\CachedDataDeploymentProcessor;
use Kinihost\Services\Source\SiteSourceService;

class SourceUploadBuildRunner extends CurrentBuildRunner {

    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * @var CachedDataDeploymentProcessor
     */
    private $cachedDataDeploymentProcessor;

    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * @var SiteDataUpdateManager
     */
    private $siteDataUpdateManager;


    /**
     * SourceUploadBuildRunner constructor.
     *
     * @param SiteSourceService $sourceService
     * @param SiteStorageManager $siteStorageManager
     * @param ObjectBinder $objectBinder
     * @param CachedDataDeploymentProcessor $cachedDataDeploymentProcessor
     * @param SiteDataUpdateManager $siteDataUpdateManager
     * @param MetaDataDeploymentProcessor $metaDataDeploymentProcessor
     * @param ContentDeploymentProcessor $contentDeploymentProcessor
     * @param SiteService $siteService
     */
    public function __construct($sourceService, $siteStorageManager, $objectBinder, $cachedDataDeploymentProcessor, $siteDataUpdateManager, $metaDataDeploymentProcessor, $contentDeploymentProcessor, $siteService) {
        parent::__construct($sourceService, $siteStorageManager, $metaDataDeploymentProcessor, $contentDeploymentProcessor);
        $this->objectBinder = $objectBinder;
        $this->siteService = $siteService;
        $this->cachedDataDeploymentProcessor = $cachedDataDeploymentProcessor;
        $this->siteDataUpdateManager = $siteDataUpdateManager;
    }

    /**
     * Run the build for source uploading.
     *
     * @param Build $build
     * @param Site $site
     */
    public function runBuild($build, $site) {


        // Convert changed objects into the correct type.
        $changedObjects = $build->getData() ?? ["changedObjects" => []];
        $changedObjects = $this->objectBinder->bindFromArray($changedObjects["changedObjects"], ChangedObject::class . "[]");


        print_r("\nApplying uploaded source to the content bucket.....");

        // Apply the uploaded source
        $this->sourceService->applyUploadedSource($build->getId(), $changedObjects, $site);

        // Update site settings as required
        $this->siteService->updateSiteSettings($site->getSiteKey(), $this->sourceService->getCurrentSiteConfig($site->getSiteKey()));

        print_r("\nCaching site data....");

        // Run the cached data deployment processor to generate any content cache items.
        $this->cachedDataDeploymentProcessor->cacheSiteData($site, $changedObjects);


        print_r("\nUpdating site data....");
        $this->siteDataUpdateManager->updateSiteData($site);

        print_r("\nBuilding to preview.....");

        // Run the current build as usual after source upload complete
        parent::runBuild($build, $site);
    }


}
