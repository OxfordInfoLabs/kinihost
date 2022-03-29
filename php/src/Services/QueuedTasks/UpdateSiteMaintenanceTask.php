<?php


namespace Kinihost\Services\QueuedTasks;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Workflow\Task\Task;
use Kinihost\Services\Site\SiteDNSManager;
use Kinihost\Services\Site\SiteRoutingManager;
use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;

class UpdateSiteMaintenanceTask implements Task {


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
     * @var EmailService
     */
    private $emailService;


    /**
     * UpdateSiteSettingsTask constructor.
     * @param SiteService $siteService
     * @param SiteRoutingManager $siteRoutingManager
     * @param SiteStorageManager $siteStorageManager
     * @param SiteDNSManager $siteDNSManager
     * @param EmailService $emailService
     */
    public function __construct($siteService, $siteRoutingManager, $siteStorageManager, $siteDNSManager, $emailService) {
        $this->siteService = $siteService;
        $this->siteRoutingManager = $siteRoutingManager;
        $this->siteStorageManager = $siteStorageManager;
        $this->siteDNSManager = $siteDNSManager;
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
            $site = $this->siteService->getSiteByKey($configuration["siteKey"] ?? null);

            // Handle
            if ($configuration["maintenance"] ?? false) {

                // Activate maintenance mode
                $this->siteDNSManager->activateMaintenanceDNS($site);

                // Send a maintenance activated email.
                $this->emailService->send(new UserTemplatedEmail($configuration["initiatingUserId"], "maintenance-activated",
                    [
                        "siteName" => $site->getTitle()
                    ]));

            } else {

                // Update the routing if required
                $routing = $this->siteRoutingManager->updateRouting($site);

                // Reset DNS back to live settings.
                $this->siteDNSManager->createServiceDNSForSite($site, $routing);

                // Send a maintenance deactivated email.
                $this->emailService->send(new UserTemplatedEmail($configuration["initiatingUserId"], "maintenance-deactivated",
                    [
                        "siteName" => $site->getTitle()
                    ]));

            }
        } catch (\Exception $e) {

            // Send a maintenance failure email.
            $this->emailService->send(new UserTemplatedEmail($configuration["initiatingUserId"], "maintenance-failed",
                [
                    "siteName" => $site->getTitle(),
                    "failureMessage" => $e->getMessage()
                ]));

        }

    }
}
