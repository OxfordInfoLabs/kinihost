<?php

namespace Kinihost\Traits\Controllers\Admin;

use Kinihost\Services\Build\BuildService;

/**
 * Class Build
 *
 */
trait Build {

    private $buildService;


    /**
     * Build constructor.
     * @param BuildService $buildService
     */
    public function __construct($buildService) {
        $this->buildService = $buildService;
    }

    /**
     * Return filtered list of builds for a site
     *
     * @http GET /list
     *
     * @param $siteId
     * @param int $limit
     * @param int $offset
     *
     * @return \Kinihost\Objects\Build\Build[]
     *
     */
    public function listBuildsForSite($siteId, $limit = 5, $offset = "0") {
        return $this->buildService->listBuildsForSite($siteId, $limit, $offset);
    }

    /**
     * Get build by ID
     *
     * @http GET /
     *
     * @param $buildId
     * @return \Kinihost\Objects\Build\Build
     */
    public function getBuild($buildId) {
        return $this->buildService->getBuild($buildId);
    }

    /**
     * Create production build
     *
     * @http GET /production/$siteKey
     *
     * @param string $siteKey
     */
    public function createProductionBuild($siteKey) {
        $this->buildService->createProductionBuild($siteKey);
    }


    /**
     * Create version revert build
     *
     * @http GET /versionRevert/$siteKey/$targetVersion
     *
     * @param string $siteKey
     * @param integer $targetVersion
     */
    public function createVersionRevertBuild($siteKey, $targetVersion) {
        $this->buildService->createVersionRevertBuild($siteKey, $targetVersion);
    }


}
