<?php

namespace Kinihost\Services\Site;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Email\AccountTemplatedEmail;
use Kiniauth\Objects\Communication\Email\SuperUserTemplatedEmail;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Communication\Email\EmailService;

use Kiniauth\Services\Security\ScopeManager;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kinikit\Core\Configuration\Configuration;

use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

use OxfordCyber\Controllers\CLI\StaticWebsite\Source;
use Kinihost\Exception\BadComponentTypeException;
use Kinihost\Exception\ComponentNotPublishedException;
use Kinihost\Exception\NoSuchComponentException;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteDomain;
use Kinihost\Objects\Site\SiteSummary;
use Kinihost\Services\Security\SiteScopeAccess;
use Kinihost\ValueObjects\Site\SiteCreateDescriptor;
use Kinihost\ValueObjects\Site\SiteSettings;
use Kinihost\ValueObjects\Site\SiteDescriptor;


/**
 * Site service for working with Site objects
 *
 * Class SiteService
 */
class SiteService {


    /**
     * @var Validator
     */
    private $validator;


    /**
     * @var SiteActivationManager
     */
    private $siteActivationManager;


    /**
     * @var QueuedTaskService
     */
    private $queuedTaskService;


    /**
     * @var EmailService
     */
    private $emailService;


    /**
     * @var ScopeManager
     */
    private $scopeManager;


    /**
     * @var SiteStorageManager
     */
    private $siteStorageManager;

    /**
     * Validator
     *
     * SiteService constructor.
     * @param Validator $validator
     * @param SiteActivationManager $siteActivationManager
     * @param QueuedTaskService $queuedTaskService
     * @param EmailService $emailService
     * @param SecurityService $securityService
     * @param ScopeManager $scopeManager
     * @param SiteStorageManager $siteStorageManager
     *
     */
    public function __construct($validator, $siteActivationManager, $queuedTaskService, $emailService, $securityService, $scopeManager, $siteStorageManager) {
        $this->validator = $validator;
        $this->siteActivationManager = $siteActivationManager;
        $this->queuedTaskService = $queuedTaskService;
        $this->emailService = $emailService;
        $this->securityService = $securityService;
        $this->scopeManager = $scopeManager;
        $this->siteStorageManager = $siteStorageManager;
    }


    /**
     * Set email service
     *
     * @param EmailService $emailService
     */
    public function setEmailService($emailService) {
        $this->emailService = $emailService;
    }

    /**
     * Return a boolean determining whether or not a site key is available.
     *
     * @param $proposedSiteKey
     */
    public function isSiteKeyAvailable($proposedSiteKey) {

        // Use the site object to validate whether or nor a site key is available.
        $site = new Site();
        $site->setSiteKey($proposedSiteKey);
        $validationErrors = $this->validator->validateObject($site);
        return !array_key_exists("siteKey", $validationErrors);
    }


    /**
     * Get a suggested site key based upon a proposed title.
     *
     * @param $proposedTitle
     */
    public function getSuggestedSiteKey($proposedTitle) {

        $suffix = "";

        do {
            $proposedKey = substr(strtolower(preg_replace("/[^A-Za-z0-9]/", "", $proposedTitle)), 0, 20) . $suffix;
            if (!$suffix) $suffix = 2; else $suffix++;
        } while (!$this->isSiteKeyAvailable($proposedKey));
        return $proposedKey;
    }


    /**
     * List sites (type of site only)
     *
     * @param string $searchString
     * @param string $type
     *
     * @param int $offset
     * @param int $limit
     *
     * @return SiteSummary[]
     */
    public function listSites($searchString = "", $offset = 0, $limit = 10) {

        return SiteSummary::filter("WHERE (title like ? or siteKey like ?)  ORDER by title LIMIT ? OFFSET ?",
            "%" . $searchString . "%", "%" . $searchString . "%", $limit, $offset);

    }


    /**
     * List all sites for a given account by id.
     *
     * @return Site[]
     */
    public function listSitesForAccount($searchString = "", $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return Site::filter("WHERE (title like ? or siteKey like ?) AND accountId = ? ORDER by title LIMIT ? OFFSET ?",
            "%" . $searchString . "%", "%" . $searchString . "%", $accountId, $limit, $offset);
    }


    /**
     * List the last 10 recent sites in use for this account.
     *
     * @param string $accountId
     */
    public function listRecentSitesForAccount($accountId = Account::LOGGED_IN_ACCOUNT) {
        return Site::filter("WHERE accountId = ? ORDER BY last_modified DESC, site_id DESC LIMIT 10", $accountId);
    }


    /**
     * List all the sites for a user
     *
     * @param string $userId
     */
    public function listSiteTitlesAndKeysForUser($userId = User::LOGGED_IN_USER) {

        $matchingTitlesAndKeys = Site::values(["DISTINCT title", "siteKey"], "WHERE accountId IN 
        (SELECT scope_id FROM ka_user_role WHERE user_id = ? AND scope = ?) 
        AND status = ?
        ORDER BY title", $userId, Role::SCOPE_ACCOUNT, Site::STATUS_ACTIVE);

        return $matchingTitlesAndKeys;

    }


    /**
     * Get a site by key.
     *
     * @param $siteKey
     * @return Site
     */
    public function getSiteByKey($siteKey) {
        $sites = Site::filter("WHERE siteKey = ?", $siteKey);
        if (sizeof($sites) > 0) {
            return $sites[0];
        } else {
            throw new ObjectNotFoundException("Site", $siteKey);
        }
    }


    /**
     * Get a site summary by key
     *
     * @param $siteKey
     * @return mixed
     *
     * @throws ObjectNotFoundException
     */
    public function getSiteSummaryByKey($siteKey) {
        $sites = SiteSummary::filter("WHERE siteKey = ?", $siteKey);
        if (sizeof($sites) > 0) {
            return $sites[0];
        } else {
            throw new ObjectNotFoundException("SiteSummary", $siteKey);
        }
    }


    /**
     * Wrapper to create function which also calls activation function
     *
     * @param SiteCreateDescriptor $siteCreateDescriptor
     * @param integer $accountId
     */
    public function initialiseComponent($siteCreateDescriptor, $accountId = Account::LOGGED_IN_ACCOUNT) {

        if (($siteCreateDescriptor->getType() != Site::TYPE_APP) && ($siteCreateDescriptor->getType() != Site::TYPE_THEME)) {
            throw new ValidationException([
                "Only themes or apps can be initialised"
            ]);
        }

        // Create a site using the descriptor and account id.
        $site = $this->createSite($siteCreateDescriptor, $accountId);

        // Queue for activation straight away.
        $this->queuedTaskService->queueTask(Configuration::readParameter("queue.name"), "activate-static",
            "Activate Static Site: " . $site->getSiteKey(), ["siteId" => $site->getSiteId()]);


        return $site;

    }


    /**
     * Create a site using the passed descriptor, for either the logged in account or
     * an explicit one.
     *
     * @param SiteDescriptor $siteCreateDescriptor
     * @param string $accountId
     *
     * @return Site
     */
    public function createSite($siteCreateDescriptor, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Resolve a site key if we need to.
        if (!$siteCreateDescriptor->getSiteKey()) {
            $siteCreateDescriptor->setSiteKey($this->getSuggestedSiteKey($siteCreateDescriptor->getTitle()));
        }


        // Create a site using the passed descriptor, save it and return
        $newSite = new Site($siteCreateDescriptor->getTitle(), $siteCreateDescriptor->getSiteKey(), $accountId);
        $newSite->save();


        // Grant full access to the logged in user if one exists
        $user = $this->securityService->getLoggedInSecurableAndAccount()[0] ?? null;
        if ($user) {

            $roles = Role::filter("WHERE scope = ?", SiteScopeAccess::SCOPE_SITE);
            foreach ($roles as $role) {
                $userRole = new UserRole(SiteScopeAccess::SCOPE_SITE, $newSite->getSiteId(), $role->getId(), $accountId, $user->getId());
                $userRole->save();

            }
        }

        // Send the setting up email if a site
        $this->emailService->send(new AccountTemplatedEmail($accountId, "setting-up",
            ["site" => $newSite]));


        return $newSite;

    }


    /**
     * Activate a site by id.
     *
     * @param $siteId
     * @objectInterceptorDisabled
     */
    public function activateSite($siteId) {

        // Grab the site
        $site = Site::fetch($siteId);

        // Activate the site using the activation manager.
        $this->siteActivationManager->activateSite($site);

        // Save the site
        $site->save();

        // Check for site activation straight away.
        $this->checkForSiteActivation($siteId);
    }


    /**
     * Confirm that activation is complete for a site.
     * This
     *
     * @param $siteId
     * @objectInterceptorDisabled
     */
    public function checkForSiteActivation($siteId, $checkNumber = 0) {

        // Grab the site
        $site = Site::fetch($siteId);

        $status = $this->siteActivationManager->getActivationStatus($site);


        // If valid, set status to active and complete, otherwise schedule a job
        if ($status && $status->isValid()) {
            $site->setStatus(Site::STATUS_ACTIVE);
            $site->save();

            if ($site->getAccountId()) {
                $this->emailService->send(new AccountTemplatedEmail($site->getAccountId(), "activated", ["site" => $site]));
            } else {
                $this->emailService->send(new SuperUserTemplatedEmail("activated", ["site" => $site]));
            }

        } else {

            $checkNumber++;

            if ($checkNumber < 25) {
                $this->queuedTaskService->queueTask(Configuration::readParameter("queue.name"), "check-activation-static", "Check Static Site Activation - " . $site->getSiteKey() . " $checkNumber / 24",
                    ["siteId" => $siteId, "checkNumber" => $checkNumber], null, 300);
            } else {
                $this->emailService->send(new SuperUserTemplatedEmail("superuser/activation-failure", ["site" => $site]));
            }

        }


    }


    /**
     * Update a site using a site update descriptor.  This is primarily used when
     * updating the site name and installed components if required.
     *
     * @param SiteDescriptor $siteUpdateDescriptor
     */
    public function updateSite($siteUpdateDescriptor) {

        // Grab the existing site
        $site = $this->getSiteByKey($siteUpdateDescriptor->getSiteKey());

        $result = "SUCCESS";

        // Set the title if set
        if ($siteUpdateDescriptor->getTitle()) {
            $site->setTitle($siteUpdateDescriptor->getTitle());
        }

        // Save the site
        $this->saveSite($site);

        return $result;


    }


    /**
     * Save a site
     *
     * @param Site $site
     */
    public function saveSite($site) {
        $site->save();
    }


    /**
     * Remove a site by key
     *
     * @param $siteKey
     */
    public function removeSiteByKey($siteKey) {
        $site = $this->getSiteByKey($siteKey);
        $site->remove();
    }

    /**
     * @param $siteKey
     * @return \Kinihost\ValueObjects\Storage\Version[]
     * @throws ObjectNotFoundException
     */
    public function getPreviousVersionsForSite($siteKey) {
        $site = $this->getSiteByKey($siteKey);

        /** @var \Kinihost\Services\Storage\VersionedStorageRoot $storageRoot */
        $storageRoot = $this->siteStorageManager->getContentRoot($site);
        return $storageRoot->getPreviousVersions();
    }

    /**
     * Return all of the site domains for a given site.
     *
     * @param $siteKey
     * @return mixed
     * @throws ObjectNotFoundException
     */
    public function getSiteDomains($siteKey) {
        $site = $this->getSiteByKey($siteKey);

        return SiteDomain::filter("WHERE site_id = ?", $site->getSiteId());
    }

    /**
     * Update site domains
     *
     * @param integer $siteId
     * @param string[] $siteDomains
     */
    public function updateSiteDomains($siteKey, $siteDomains = []) {
        // Grab the site
        $site = $this->getSiteByKey($siteKey);

        /**
         * Loop through and add site domains
         */
        $newSiteDomains = [];
        foreach ($siteDomains as $index => $siteDomain) {
            $newSiteDomains[] = new SiteDomain($siteDomain, $index ? SiteDomain::TYPE_SECONDARY : SiteDomain::TYPE_PRIMARY);
        }

        $site->setSiteDomains($newSiteDomains);
        $this->saveSite($site);

        $initiatingUser = $this->securityService->getLoggedInUserAndAccount()[0] ?? null;
        $initiatingUserId = $initiatingUser ? $initiatingUser->getId() : null;

        // Queue task to update routing.
        $this->queuedTaskService->queueTask(
            Configuration::readParameter("queue.name"),
            "update-static-settings",
            "Update routing for site " . $site->getSiteKey(),
            ["siteKey" => $siteKey, "initiatingUserId" => $initiatingUserId, "routingUpdate" => true, "storageUpdate" => true]
        );
    }

    /**
     *
     * @param $siteKey
     * @return SiteSettings
     */
    public function getSiteSettings($siteKey) {

        $site = $this->getSiteByKey($siteKey);

        $siteConfig = $site->getConfig();

        $siteSettings = new SiteSettings($siteConfig->getIndexPage(), $siteConfig->getNotFoundPage(), $siteConfig->getPublishDirectory());

        return $siteSettings;
    }

    /**
     * Update the settings for the site
     *
     * @param $siteKey
     * @param SiteSettings $siteSettings
     */
    public function updateSiteSettings($siteKey, $siteSettings) {

        $savedSettings = $this->getSiteSettings($siteKey);

        // Get the site
        $site = $this->getSiteByKey($siteKey);

        $initiatingUser = $this->securityService->getLoggedInSecurableAndAccount()[0] ?? null;
        $initiatingUserId = $initiatingUser ? $initiatingUser->getId() : null;

        $updateConfig = ["siteKey" => $siteKey, "initiatingUserId" => $initiatingUserId];

        // Now derive the required changes
        if ($savedSettings->getPublishDirectory() != $siteSettings->getPublishDirectory()) {
            $updateConfig["previewBuild"] = true;
            $site->getConfig()->setPublishDirectory($siteSettings->getPublishDirectory());
        }

        if ($savedSettings->getIndexPage() != $siteSettings->getIndexPage() || $savedSettings->getNotFoundPage() != $siteSettings->getNotFoundPage()) {
            $updateConfig["storageUpdate"] = true;
            $site->getConfig()->setIndexPage($siteSettings->getIndexPage());
            $site->getConfig()->setNotFoundPage($siteSettings->getNotFoundPage());
        }

        // Apply the new changes
        $site->save();

        // Queue task to update settings.
        $this->queuedTaskService->queueTask(
            Configuration::readParameter("queue.name"), "update-static-settings", "Update settings for site " . $site->getSiteKey(), $updateConfig);


    }

    /**
     * Set whether the site is in maintenance mode
     *
     * @param $siteKey
     * @param $maintenanceMode
     */
    public function updateMaintenanceMode($siteKey, $maintenanceMode) {
        $site = $this->getSiteByKey($siteKey);
        $site->setMaintenanceMode($maintenanceMode);
        $this->saveSite($site);

        $initiatingUser = $this->securityService->getLoggedInSecurableAndAccount()[0] ?? null;
        $initiatingUserId = $initiatingUser ? $initiatingUser->getId() : null;


        // Queue task to update settings.
        $this->queuedTaskService->queueTask(
            Configuration::readParameter("queue.name"), "update-static-maintenance", "Update maintenance mode for site " . $site->getSiteKey(),
            ["siteKey" => $siteKey, "initiatingUserId" => $initiatingUserId, "maintenance" => $maintenanceMode]);


        return $site;
    }


    /**
     * Check site components are valid for attachment to a site
     *
     * @param SiteComponentDescriptor[] $descriptors
     * @objectInterceptorDisabled
     */
    public function getSiteComponentsFromDescriptors($descriptors, $currentVersions = []) {

        $siteComponents = [];

        // Check the validity of any components supplied
        foreach ($descriptors ?? [] as $componentDescriptor) {
            $componentSiteKey = $componentDescriptor->getComponentSiteKey();
            try {
                $components = SiteSummary::filter("WHERE siteKey = ?", $componentSiteKey);
                if (sizeof($components) == 0) {
                    throw new ObjectNotFoundException(SiteSummary::class, $componentSiteKey);
                }
                $component = $components[0];

                // Check for bad component types
                if ($component->getType() != Site::TYPE_APP && $component->getType() != Site::TYPE_THEME) {
                    throw new BadComponentTypeException($componentSiteKey);
                }

                // Ensure we have a published version
                if (!$component->getPublishedVersion()) {
                    throw new ComponentNotPublishedException($componentSiteKey);
                }

                $installedVersion = null;
                if (!$componentDescriptor->isUpdate() && isset($currentVersions[$componentSiteKey])) {
                    $installedVersion = $currentVersions[$componentSiteKey];
                }

                $siteComponents[] = new SiteComponent($component, $componentDescriptor->getMountPoint(), $installedVersion);

            } catch (ObjectNotFoundException $e) {
                throw new NoSuchComponentException($componentSiteKey);
            }
        }

        return $siteComponents;

    }


}
