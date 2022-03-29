<?php


namespace Kinihost\Services\Build\Runner;


use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;
use Kinihost\Services\Site\SiteSourceService;
use Kinihost\Services\Site\SiteStorageManager;

/**
 * Version revert build runner
 *
 * Class VersionRevertBuildRunner
 * @package Kinihost\Services\Build\Runner
 */
class VersionRevertBuildRunner {


    /**
     * @var SiteStorageManager
     */
    protected $siteStorageManager;


    /**
     * VersionRevertBuildRunner constructor.
     *
     * @param SiteSourceService $siteStorageManager
     */
    public function __construct($siteStorageManager) {
        $this->siteStorageManager = $siteStorageManager;
    }

    /**
     * Run build
     *
     * @param Build $build
     * @param Site $site
     */
    public function runBuild($build, $site) {

        // Get the content root
        $contentRoot = $this->siteStorageManager->getContentRoot($site);
        $contentRoot->revertToPreviousVersion($build->getData()["targetVersion"]);

    }


}
