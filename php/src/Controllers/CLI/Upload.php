<?php


namespace Kinihost\Controllers\CLI;


use Kinihost\Services\Site\SiteService;
use Kinihost\Services\Site\SiteStorageManager;


class Upload {


    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * @var SiteStorageManager
     */
    private $siteStorageManager;


    /**
     * Upload constructor.
     *
     * @param SiteService $siteService
     * @param SiteStorageManager $siteStorageManager
     */
    public function __construct($siteService, $siteStorageManager) {
        $this->siteService = $siteService;
        $this->siteStorageManager = $siteStorageManager;
    }

    /**
     * @http PUT /
     *
     * @param mixed $payload
     * @unsanitise payload
     */
    public function uploadFile($payload) {
        $uploadUrl = $payload["url"];
        $explodedUrl = explode("/upload/", $uploadUrl);
        $site = $this->siteService->getSiteByKey($payload["siteKey"]);

        // Upload the file
        $this->siteStorageManager->getUploadRoot($site)->saveObject(array_pop($explodedUrl), $payload["body"]);

        return true;
    }


}