<?php


namespace Kinihost\ValueObjects\Site;


use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteConfig;

/**
 * Lighter weight site object for client use.
 *
 * Class SiteSummary
 * @package Kinihost\ValueObjects\Site
 */
class SiteSummary {

    /**
     * @var string
     */
    private $siteKey;

    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $lastModified;


    /**
     * @var integer
     */
    private $lastBuildNumber;


    /**
     * @var SiteConfig
     */
    private $config;


    /**
     * SiteSummary constructor.
     *
     * @param \Kinihost\Objects\Site\SiteSummary $site
     */
    public function __construct($site) {
        $this->siteKey = $site->getSiteKey();
        $this->title = $site->getTitle();
        $this->status = $site->getStatus();
        $this->lastModified = $site->getLastModified() instanceof \DateTime ? $site->getLastModified()->format("Y-m-d H:i:s") : null;

        if ($site instanceof Site) {
            $this->config = $site->getConfig();
            $this->lastBuildNumber = $site->getLastBuildNumber();
        }
    }

    /**
     * @return string
     */
    public function getSiteKey() {
        return $this->siteKey;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return ucfirst(strtolower($this->status));
    }


    /**
     * @return \DateTime
     */
    public function getLastModified() {
        return $this->lastModified;
    }

    /**
     * @return int
     */
    public function getLastBuildNumber() {
        return $this->lastBuildNumber;
    }


    /**
     * @return SiteConfig
     */
    public function getConfig() {
        return $this->config;
    }


}
