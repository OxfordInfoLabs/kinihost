<?php

namespace Kinihost\Services\QueuedTasks;

use Kiniauth\Services\Workflow\QueuedTask\QueuedTask;
use Kinihost\Services\Build\BuildService;

class RunSiteBuildTask implements QueuedTask {

    /**
     * @var BuildService
     */
    private $buildService;


    /**
     * Constructor for running a build task.
     *
     * @param BuildService $buildService
     */
    public function __construct($buildService) {
        $this->buildService = $buildService;
    }

    /**
     * Run method for a queued task.  Returns true or false
     * according to whether this task was successful or failed.
     *
     * @param string[string] $configuration
     * @return boolean
     */
    public function run($configuration) {
        $buildId = $configuration["buildId"];
        $this->buildService->runBuild($buildId);
    }
}
