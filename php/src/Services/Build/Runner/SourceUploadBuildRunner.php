<?php


namespace Kinihost\Services\Build\Runner;


use Kinikit\Core\Binding\ObjectBinder;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;

use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteSourceService;

class SourceUploadBuildRunner implements BuildRunner {


    /**
     * @var SiteService
     */
    private $siteService;


    /**
     * @var SiteSourceService
     */
    private $siteSourceService;


    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * SourceUploadBuildRunner constructor.
     *
     *
     * @param SiteService $siteService
     * @param SiteSourceService $siteSourceService
     * @param ObjectBinder $objectBinder
     */
    public function __construct($siteService, $siteSourceService, $objectBinder) {
        $this->objectBinder = $objectBinder;
        $this->siteSourceService = $siteSourceService;
        $this->siteService = $siteService;
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
        $this->siteSourceService->applyUploadedSource($build->getId(), $changedObjects, $site);

        // Update site settings as required
        $this->siteService->updateSiteSettings($site->getSiteKey(), $this->siteSourceService->getCurrentSiteConfig($site->getSiteKey()));

    }


}
