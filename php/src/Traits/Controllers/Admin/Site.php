<?php

namespace Kinihost\Traits\Controllers\Admin;

use Kinihost\Services\Site\SiteService;
use Kinihost\ValueObjects\Site\SiteSettings;
use Kinihost\ValueObjects\Site\SiteSummary;
use Kinihost\ValueObjects\Site\SiteDescriptor;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

/**
 * Site management for super admin
 *
 * Class Site
 */
trait Site {

    /**
     * @var SiteService
     */
    private $siteService;


    /**
     * Site constructor.
     *
     * @param SiteService $siteService
     */
    public function __construct($siteService) {
        $this->siteService = $siteService;
    }

    /**
     *
     * @http GET /$siteKey
     *
     * Get a site by key
     *
     * @param $siteKey
     * @return \Kinihost\Objects\Site\Site
     */
    public function getSite($siteKey) {
        return $this->siteService->getSiteByKey($siteKey);
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
     * List all sites optionally limiting by a search string with offsets and limits
     *
     * @http GET /
     *
     * @param string $searchString
     * @param int $offset
     * @param int $limit
     *
     * @return SiteSummary[]
     */
    public function listSites($searchString = "", $offset = 0, $limit = 10) {
        return array_map(function ($element) {
            return new SiteSummary($element);
        }, $this->siteService->listSites($searchString, $offset, $limit));
    }


    /**
     * Create a site using a title
     *
     * @http POST /
     *
     * @param SiteDescriptor $siteDescriptor
     */
    public function createSite($siteDescriptor) {
        $this->siteService->createSite($siteDescriptor, 0);
    }


    /**
     * Update a site using a site update descriptor
     *
     * @http PUT /
     *
     * @param SiteDescriptor $siteDescriptor
     */
    public function updateSite($siteDescriptor) {
        $this->siteService->updateSite($siteDescriptor);
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


}