<?php

namespace Kinihost\Objects\Site;


use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\FieldValidationError;
use Kinihost\Objects\Build\Build;

/**
 * Class Site
 *
 * @table kh_site
 * @generate
 */
class Site extends SiteSummary {


    /**
     * The account id which this site belongs to.
     *
     * @var integer
     * @required
     */
    private $accountId;


    /**
     * The dns provider in use for this site.
     *
     * @var string
     */
    private $dnsProviderKey;

    /**
     * The routing provider in use for this site.
     *
     * @var string
     */
    private $routingProviderKey;


    /**
     * @oneToMany
     * @childJoinColumns site_id
     *
     * @var SiteDomain[]
     */
    private $siteDomains = [];


    /**
     * Last time a preview build was completed
     *
     * @var \DateTime
     */
    private $lastPreviewed;


    /**
     * Last time a production build was completed
     *
     * @var \DateTime
     */
    private $lastPublished;


    /**
     * @var integer
     */
    private $lastBuildNumber = 0;


    /**
     * @var integer
     */
    private $maintenanceMode = 0;


    /**
     * Construct a new site.
     *
     * Site constructor.
     * @param string $title
     * @param string $siteKey
     * @param integer $accountId
     */
    public function __construct($title = null, $siteKey = null, $accountId = null) {

        // Construct parent
        parent::__construct($title, $siteKey);

        $this->accountId = $accountId;
        $this->config = new SiteConfig();

        // Initialise array of site domains with production domain.
        $this->siteDomains = [new SiteDomain($siteKey . "-production." . Configuration::readParameter("kinihost.service.domain"))];


    }


    /**
     * Useful for testing
     *
     * @param int $siteId
     */
    public function updateSiteId($siteId) {
        $this->siteId = $siteId;
    }


    public function getDescription() {
        return $this->title . " (" . $this->siteKey . ")";
    }


    /**
     * @param string $siteKey
     */
    public function setSiteKey($siteKey) {
        $this->siteKey = $siteKey;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @param string $storageProviderKey
     */
    public function setStorageProviderKey($storageProviderKey) {
        $this->storageProviderKey = $storageProviderKey;
    }

    /**
     * @return string
     */
    public function getRoutingProviderKey() {
        return $this->routingProviderKey;
    }

    /**
     * @param string $routingProviderKey
     */
    public function setRoutingProviderKey($routingProviderKey) {
        $this->routingProviderKey = $routingProviderKey;
    }

    /**
     * @return string
     */
    public function getDnsProviderKey() {
        return $this->dnsProviderKey;
    }

    /**
     * @param string $dnsProviderKey
     */
    public function setDnsProviderKey($dnsProviderKey) {
        $this->dnsProviderKey = $dnsProviderKey;
    }

    /**
     * @param string $serviceDomain
     */
    public function setServiceDomain($serviceDomain) {
        $this->serviceDomain = $serviceDomain;
    }


    /**
     * @param SiteConfig $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * @param mixed[] $providerSettings
     */
    public function setProviderSettings($providerSettings) {
        $this->providerSettings = $providerSettings;
    }


    /**
     * Set a provider setting
     *
     * @param $settingKey
     * @param $settingValue
     */
    public function setProviderSetting($settingKey, $settingValue) {
        if (!$this->providerSettings) {
            $this->providerSettings = [];
        }
        $this->providerSettings[$settingKey] = $settingValue;
    }

    /**
     * @return SiteDomain
     */
    public function getSiteDomains() {
        return $this->siteDomains;
    }

    /**
     * @param SiteDomain[] $siteDomains
     */
    public function setSiteDomains($siteDomains) {
        $this->siteDomains = $siteDomains;
    }


    /**
     * @return \DateTime
     */
    public function getLastPreviewed() {
        return $this->lastPreviewed;
    }

    /**
     * Set the last previewed date etc after a successful preview build
     */
    public function registerPreviewBuild($save = true) {
        $this->lastPreviewed = new \DateTime();
        if ($save)
            $this->save();
    }

    /**
     * @return \DateTime
     */
    public function getLastPublished() {
        return $this->lastPublished;
    }

    /**
     * Increment the published version and last published date
     * following a successful published build.
     */
    public function registerPublishedBuild($save = true) {
        $this->lastPublished = new \DateTime();
        if ($save)
            $this->save();
    }


    /**
     * @return int
     */
    public function getLastBuildNumber() {
        return $this->lastBuildNumber;
    }

    /**
     * @return Build
     */
    public function getLastBuild() {
        return $this->lastBuild;
    }

    /**
     * @return bool
     */
    public function isMaintenanceMode() {
        return (bool)$this->maintenanceMode;
    }

    /**
     * @param bool $maintenanceMode
     */
    public function setMaintenanceMode($maintenanceMode) {
        $this->maintenanceMode = (int)$maintenanceMode;
    }


    /**
     * Increment the last build number for the site
     */
    public function incrementLastBuildNumber() {
        $this->lastBuildNumber++;
        $this->save();
    }


    /**
     * Update timestamp on save.
     */
    public function save() {
        $this->lastModified = new \DateTime();
        parent::save();
    }


    /**
     * Add validation to ensure uniqueness of site key
     *
     * @return array
     */
    public function validate() {

        $validationErrors = [];

        // If we have a duplicate site key, complain.
        if (self::values("COUNT(*) total", "WHERE siteKey = ? AND siteId <> ?", $this->siteKey, $this->siteId ? $this->siteId : "")[0]) {
            $validationErrors["siteKey"]["unique"] = new FieldValidationError("siteKey", "unique", "The site key supplied is already in use");
        }

        return $validationErrors;
    }


}
