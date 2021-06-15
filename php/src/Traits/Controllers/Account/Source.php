<?php

namespace Kinihost\Traits\Controllers\Account;

use Kinihost\Services\Site\SiteSourceService;

trait Source {

    /**
     * @var SiteSourceService
     */
    private $siteSourceService;

    /**
     * Source constructor.
     * @param SiteSourceService $siteSourceService
     */
    public function __construct($siteSourceService) {
        $this->siteSourceService = $siteSourceService;
    }


    /**
     * List a source folder or sub folder.
     *
     * @http GET /list
     *
     * @param $siteKey
     * @param string $subFolder
     */
    public function listFolder($siteKey, $subFolder = "") {
        return $this->siteSourceService->listCurrentSourceForSite($siteKey, $subFolder);
    }

}
