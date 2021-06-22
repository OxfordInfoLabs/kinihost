<?php


namespace Kinihost\Controllers\CLI\StaticWebsite;

use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinihost\Exception\SiteNotActiveException;
use Kinihost\Services\Site\SiteService;
use Kinihost\ValueObjects\Site\SiteSummary;

class Site {

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
     * Get a site key or throw object not found exception
     *
     * @http GET /$siteKey
     *
     * @param string $siteKey
     * @throws ObjectNotFoundException
     */
    public function getSite($siteKey) {
        $site = $this->siteService->getSiteByKey($siteKey);
        if ($site->getStatus() == \Kinihost\Objects\Site\Site::STATUS_ACTIVE) {
            return new SiteSummary($this->siteService->getSiteByKey($siteKey));
        } else {
            throw new SiteNotActiveException($siteKey);
        }
    }


    /**
     * List all the sites for authenticated user
     *
     * @http GET /
     *
     * @return array
     */
    public function listSites() {
        return $this->siteService->listSiteTitlesAndKeysForUser();
    }


}
