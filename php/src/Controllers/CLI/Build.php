<?php


namespace Kinihost\Controllers\CLI;


use Kinihost\Services\Build\BuildService;

class Build {

    /**
     * @var BuildService
     */
    private $buildService;


    /**
     * Build constructor.
     *
     * @param BuildService $buildService
     */
    public function __construct($buildService) {
        $this->buildService = $buildService;
    }


    /**
     * @http GET /queue/$buildId
     *
     * @param integer $buildId
     */
    public function queueBuild($buildId) {
        $this->buildService->queueBuild($buildId);
    }


}