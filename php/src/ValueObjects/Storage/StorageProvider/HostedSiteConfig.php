<?php


namespace Kinihost\ValueObjects\Storage\StorageProvider;

/**
 * Config options for hosted sites where the storage provider is being configured
 * in preparation for hosted sites. e.g. Google / AWS bucket.
 *
 * Class HostedSiteConfig
 * @package Kinihost\ValueObjects\Storage\StorageProvider
 */
class HostedSiteConfig {

    /**
     * @var string
     */
    private $indexPage = "index.html";

    /**
     * @var string
     */
    private $errorPage = "404.html";

    /**
     * HostedSiteConfig constructor.
     *
     * @param string $indexPage
     * @param string $errorPage
     */
    public function __construct($indexPage = "index.html", $errorPage = "404.html") {
        $this->indexPage = $indexPage;
        $this->errorPage = $errorPage;
    }


    /**
     * @return string
     */
    public function getIndexPage() {
        return $this->indexPage;
    }

    /**
     * @return string
     */
    public function getErrorPage() {
        return $this->errorPage;
    }


}
