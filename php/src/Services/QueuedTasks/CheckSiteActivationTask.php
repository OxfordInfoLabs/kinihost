<?php


namespace Kinihost\Services\QueuedTasks;


use Kiniauth\Services\Workflow\QueuedTask\QueuedTask;
use Kinihost\Services\Site\SiteService;

class CheckSiteActivationTask implements QueuedTask {

    /**
     * @var SiteService
     */
    private $siteService;


    /**
     * Constructor for site activation
     *
     * @param SiteService $siteService
     */
    public function __construct($siteService) {
        $this->siteService = $siteService;
    }

    /**
     * Run method for a queued task.  Returns true or false
     * according to whether this task was successful or failed.
     *
     * @param string[string] $configuration
     * @return boolean
     */
    public function run($configuration) {

        $siteId = $configuration["siteId"];
        $checkNumber = $configuration["checkNumber"];

        $this->siteService->checkForSiteActivation($siteId, $checkNumber);
    }

}
