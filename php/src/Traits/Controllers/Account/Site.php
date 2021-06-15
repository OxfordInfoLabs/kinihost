<?php

namespace Kinihost\Traits\Controllers\Account;

use Kinihost\Services\Site\SiteService;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinihost\ValueObjects\Site\SiteSettings;

trait Site {

    private $siteService;

    /**
     * Site constructor.
     * @param SiteService $siteService
     */
    public function __construct($siteService) {
        $this->siteService = $siteService;
    }

    /**
     * Save a site
     *
     * @http POST /save
     *
     * @param \OxfordCyber\StaticWebsite\Objects\Site\Site $site
     * @return mixed
     */
    public function saveSite($site) {
        $this->siteService->saveSite($site);
        return $site;
    }

    /**
     * Save site domains for site
     *
     * @http POST /siteDomains
     *
     * @param string[] $siteDomains
     * @param string $siteKey
     */
    public function updateSiteDomains($siteDomains, $siteKey) {
        $this->siteService->updateSiteDomains($siteKey, $siteDomains);
    }


    /**
     * Get the sites for the logged in Account
     *
     * @http GET /list
     *
     * @return \Kinihost\Objects\Site\Site[]
     */
    public function getSites() {
        return $this->siteService->listSitesForAccount();
    }

    /**
     * Return the site details by the site key
     *
     * @http GET /
     *
     * @param $siteKey
     * @return \Kinihost\Objects\Site\Site
     * @throws ObjectNotFoundException
     */
    public function getSite($siteKey) {
        return $this->siteService->getSiteByKey($siteKey);
    }

    /**
     * Return the current and previous storage
     *
     * @http GET /versions
     *
     * @param $siteKey
     * @return array
     */
    public function getPreviousVersions($siteKey) {
        return $this->siteService->getPreviousVersionsForSite($siteKey);
    }

    /**
     * Get the settings for the site
     *
     * @http GET /settings
     *
     * @param $siteKey
     * @return SiteSettings
     */
    public function getSiteSettings($siteKey) {
        return $this->siteService->getSiteSettings($siteKey);
    }


    /**
     * Update the site settings
     *
     * @http POST /updateSettings
     *
     * @param $siteKey
     * @param SiteSettings $siteSettings
     */
    public function updateSiteSettings($siteSettings, $siteKey) {
        $this->siteService->updateSiteSettings($siteKey, $siteSettings);
    }

    /**
     * Return the site domains for a site
     *
     * @http GET /domains
     *
     * @param $siteKey
     * @return mixed
     * @throws ObjectNotFoundException
     */
    public function getSiteDomains($siteKey) {
        return $this->siteService->getSiteDomains($siteKey);
    }

    /**
     * Update the maintenance mode
     *
     * @http GET /maintenance
     *
     * @param $siteKey
     * @param $maintenanceMode
     */
    public function updateMaintenanceMode($siteKey, $maintenanceMode) {
        return $this->siteService->updateMaintenanceMode($siteKey, $maintenanceMode);
    }

    /**
     * Return a suggested site key based on the supplied title
     *
     * @http GET /suggestedSiteKey
     *
     * @param $proposedTitle
     * @return string
     */
    public function getSuggestedSiteKey($proposedTitle) {
        return $this->siteService->getSuggestedSiteKey($proposedTitle);
    }

    /**
     * Check if a site key is available
     *
     * @http GET /siteKeyAvailable
     *
     * @param $proposedSiteKey
     * @return bool
     */
    public function isSiteKeyAvailable($proposedSiteKey) {
        return $this->siteService->isSiteKeyAvailable($proposedSiteKey);
    }
}
