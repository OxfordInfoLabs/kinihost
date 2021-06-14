<?php


namespace Kinihost\Objects\Site;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Base site class for encoding core stuff
 *
 * @table kh_site
 * @readOnly
 */
class SiteSummary extends ActiveRecord {


    /**
     * The primary key for this site
     *
     * @var integer
     * @primaryKey
     * @autoIncrement
     */
    protected $siteId;


    /**
     * A simple lowercase alphanumerical string which uniquely
     * identifies this site and also acts as the subdomain for
     * the control domain for this site.
     *
     * @var string
     * @required
     * @regexp([a-z0-9]+)
     */
    protected $siteKey;


    /**
     * Site title (descriptive)
     *
     * @var string
     * @required
     */
    protected $title;

    /**
     * The service domain used for this site.
     *
     * @var string
     */
    protected $serviceDomain;

    /**
     * The storage provider in use for this site.
     *
     * @var string
     */
    protected $storageProviderKey;


    /**
     * Adhoc provider settings for use by providers for maintaining state.
     *
     * @json
     * @var mixed[]
     */
    protected $providerSettings = [];

    /**
     * Last modified timestamp
     *
     * @var \DateTime
     */
    protected $lastModified;

    /**
     * @var string
     */
    protected $status = self::STATUS_PENDING;

    // Site statuses - default to pending
    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_SUSPENDED = "SUSPENDED";
    const STATUS_DELETED = "DELETED";

    /**
     * Key/value pair configuration as required.
     *
     * @var SiteConfig
     * @json
     * @sqlType LONGTEXT
     */
    protected $config;


    /**
     * SiteSummary constructor. Most useful for testing
     *
     * @param string $title
     * @param string $siteKey
     */
    public function __construct($title = null, $siteKey = null) {
        $this->title = $title;
        $this->siteKey = $siteKey;
    }


    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getSiteId() {
        return $this->siteId;
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
    public function getServiceDomain() {
        return $this->serviceDomain;
    }

    /**
     * @return mixed[]
     */
    public function getProviderSettings() {
        return $this->providerSettings;
    }

    /**
     * @return string
     */
    public function getStorageProviderKey() {
        return $this->storageProviderKey;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified() {
        return $this->lastModified;
    }

    /**
     * @return SiteConfig
     */
    public function getConfig() {
        return $this->config;
    }
}