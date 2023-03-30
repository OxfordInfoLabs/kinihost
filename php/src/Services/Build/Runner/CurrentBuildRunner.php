<?php


namespace Kinihost\Services\Build\Runner;

use Kinihost\Services\Site\SiteSourceService;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinihost\Objects\Build\Build;
use Kinihost\Objects\Site\Site;

use Kinihost\Services\Site\SiteStorageManager;

/**
 * Core build runner which builds from the current source and content into the specified target.
 *
 * Class CurrentBuildRunner
 */
class CurrentBuildRunner implements BuildRunner {


    /**
     * @var SiteSourceService
     */
    protected $sourceService;

    /**
     * @var SiteStorageManager
     */
    protected $siteStorageManager;


    /**
     * CurrentBuildRunner constructor.
     *
     * @param SiteSourceService $sourceService
     * @param SiteStorageManager $siteStorageManager
     */
    public function __construct($sourceService, $siteStorageManager) {
        $this->sourceService = $sourceService;
        $this->siteStorageManager = $siteStorageManager;
    }


    /**
     * Run the build based upon the current source
     *
     * @param Build $build
     * @param Site $site
     */
    public function runBuild($build, $site) {


        $targetRoot = null;
        if ($build->getBuildType() == Build::TYPE_PREVIEW) {
            $targetRoot = $this->siteStorageManager->getPreviewRoot($site);
        } else if ($build->getBuildType() == Build::TYPE_PUBLISH) {
            $targetRoot = $this->siteStorageManager->getProductionRoot($site);
        }


        // Replace the target.
        if ($targetRoot) {

            // Get the set of current deployment files as changed files array
            $deployFiles = $this->sourceService->getCurrentDeploymentChangedFiles($site);
            $deployFiles = ObjectArrayUtils::indexArrayOfObjectsByMember("objectKey", $deployFiles ?? []);

            if ($deployFiles) {
                // Replace all the source with the deploy files
                $targetRoot->replaceAll($deployFiles);
            }

        }


    }
}
