<?php


namespace Kinihost\Services\Build\Runner;


use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;

/**
 * Version revert build runner
 *
 * Class VersionRevertBuildRunner
 * @package Kinihost\Services\Build\Runner
 */
class VersionRevertBuildRunner extends CurrentBuildRunner {


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

        // Run the parent build.
        parent::runBuild($build, $site);
    }


}
