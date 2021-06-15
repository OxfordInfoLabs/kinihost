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
     * @var string
     */
    private $lastBuildUser;


    /**
     * @var \DateTime
     */
    private $lastBuildTime;

    /**
     * @var SiteConfig
     */
    private $config;


    /**
     * @var string
     */
    private $type;


    /**
     * @var integer
     */
    private $publishedVersion;


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
        $this->type = $site->getType();
        $this->publishedVersion = $site->getPublishedVersion();

        if ($site instanceof Site) {
            $this->config = $site->getConfig();
            $this->lastBuildNumber = $site->getLastBuildNumber();
            $this->lastBuildTime = $site->getLastBuild() ? $site->getLastBuild()->getCreatedDate() : null;
            $this->lastBuildUser = $site->getLastBuild() && $site->getLastBuild()->getInitiatingUser() ? $site->getLastBuild()->getInitiatingUser()->getName() : null;
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
     * @return string
     */
    public function getType(): string {
        return ucfirst(strtolower($this->type));
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
     * @return string
     */
    public function getLastBuildUser() {
        return $this->lastBuildUser;
    }

    /**
     * @return string
     */
    public function getLastBuildTime() {
        return $this->lastBuildTime ? $this->lastBuildTime->format("d/m/Y H:i:s") : null;
    }


    /**
     * @return SiteConfig
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @return integer
     */
    public function getPublishedVersion() {
        return $this->publishedVersion ? $this->publishedVersion : "N/A";
    }


}
