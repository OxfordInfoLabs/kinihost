<?php

namespace Kinihost\Traits\Controllers\Admin;

use Kinihost\Services\Site\SiteService;
use Kinihost\ValueObjects\Site\SiteSummary;
use Kinihost\ValueObjects\Site\SiteDescriptor;

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
     * Update a site using a site update descriptor
     *
     * @http PUT /
     *
     * @param SiteDescriptor $siteDescriptor
     */
    public function updateSite($siteDescriptor) {
        $this->siteService->updateSite($siteDescriptor);
    }

}