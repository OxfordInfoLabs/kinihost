<?php


namespace Kinihost\Controllers\CLI;


use Kinihost\ValueObjects\Build\SourceUploadBuild;
use Kinihost\ValueObjects\Storage\ChangedObject;
use Kinihost\Services\Site\SiteSourceService;

class Source {


    /**
     * Source service
     *
     * @var SiteSourceService
     */
    private $sourceService;


    /**
     * Source constructor.
     *
     * @param SiteSourceService $sourceService
     */
    public function __construct($sourceService) {
        $this->sourceService = $sourceService;
    }

    /**
     * Get the current set of footprints for source objects in the remote storage.
     *
     * @http GET /footprints/$siteKey
     *
     */
    public function getSourceObjectFootprints($siteKey) {
        return $this->sourceService->getCurrentSourceObjectFootprints($siteKey);
    }


    /**
     * Create source upload build
     *
     * @http POST /upload/create/$siteKey
     *
     * @param string $siteKey
     * @param ChangedObject[] $changedObjects
     *
     * @return SourceUploadBuild
     */
    public function createSourceUploadBuild($siteKey, $changedObjects) {
        return $this->sourceService->createSourceUploadBuild($siteKey, $changedObjects);
    }


    /**
     * Create a set of download urls for the passed object keys
     *
     * @http POST /download/create/$siteKey
     *
     * @param string $siteKey
     * @param string[] $objectKeys
     *
     * @return string[string]
     */
    public function createDownloadUrls($siteKey, $objectKeys) {
        return $this->sourceService->createSourceDownloadURLs($siteKey, $objectKeys);
    }

}
