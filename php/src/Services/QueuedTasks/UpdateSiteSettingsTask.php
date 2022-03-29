<?php


namespace Kinihost\Services\QueuedTasks;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Workflow\Task\Task;
use Kinihost\Objects\Build\Build;
use Kinihost\Services\Build\BuildService;
use Kinihost\Services\Site\SiteDNSManager;
use Kinihost\Services\Site\SiteRoutingManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;

class UpdateSiteSettingsTask implements Task {


    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * @var SiteRoutingManager
     */
    private $siteRoutingManager;


    /**
     * @var SiteStorageManager
     */
    private $siteStorageManager;


    /**
     * @var SiteDNSManager
     */
    private $siteDNSManager;


    /**
     * @var BuildService
     */
    private $buildService;


    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * UpdateSiteSettingsTask constructor.
     * @param SiteService $siteService
     * @param SiteRoutingManager $siteRoutingManager
     * @param SiteStorageManager $siteStorageManager
     * @param SiteDNSManager $siteDNSManager
     * @param BuildService $buildService
     * @param EmailService $emailService
     */
    public function __construct($siteService, $siteRoutingManager, $siteStorageManager, $siteDNSManager, $buildService, $emailService) {
        $this->siteService = $siteService;
        $this->siteRoutingManager = $siteRoutingManager;
        $this->siteStorageManager = $siteStorageManager;
        $this->siteDNSManager = $siteDNSManager;
        $this->buildService = $buildService;
        $this->emailService = $emailService;
    }


    /**
     * Run method for a queued task.  Returns true or false
     * according to whether this task was successful or failed.
     *
     * @param string[string] $configuration
     * @return boolean
     */
    public function run($configuration) {

        try {

            $site = $this->siteService->getSiteByKey($configuration["siteKey"]);

            if (isset($configuration["previewBuild"])) {
                $this->buildService->createBuild($site->getSiteKey(), Build::TYPE_CURRENT);
            }

            if (isset($configuration["storageUpdate"])) {
                $this->siteStorageManager->updateStorage($site);
            }

            if (isset($configuration["routingUpdate"])) {
                $routing = $this->siteRoutingManager->updateRouting($site);
                if (!$site->isMaintenanceMode()) {
                    $this->siteDNSManager->createServiceDNSForSite($site, $routing);
                }

            }


            // If initiating user id passed, send an email
            if (isset($configuration["initiatingUserId"])) {
                $this->emailService->send(new UserTemplatedEmail($configuration["initiatingUserId"], "settings-updated",
                    ["siteName" => $site->getTitle()]));
            }

        } catch (\Exception $e) {

            // If initiating user id passed, send an email
            if (isset($configuration["initiatingUserId"])) {
                $this->emailService->send(new UserTemplatedEmail($configuration["initiatingUserId"], "settings-failed",
                    ["siteName" => $site->getTitle(),
                        "failureMessage" => $e->getMessage()]));
            }

        }

    }
}
