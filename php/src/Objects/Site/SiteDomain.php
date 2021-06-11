<?php


namespace Kinihost\Objects\Site;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class SiteDomain
 * @package OxfordCyber\StaticWebsite\Objects\Site
 *
 * @table kh_site_domain
 * @generate
 */
class SiteDomain extends ActiveRecord {

    const TYPE_PRIMARY = "PRIMARY";
    const TYPE_SECONDARY = "SECONDARY";

    const STATUS_PENDING = "PENDING";
    const STATUS_ACTIVE = "ACTIVE";

    /**
     * The primary key for this site
     *
     * @var integer
     * @primaryKey
     * @autoIncrement
     */
    private $id;

    /**
     * @var integer
     */
    private $siteId;

    /**
     * @var string
     */
    private $domainName;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $status = self::STATUS_PENDING;


    /**
     * Construct new site domain
     *
     * SiteDomain constructor.
     *
     * @param $domainName
     * @param string $type
     * @param integer $siteId
     */
    public function __construct($domainName, $type = SiteDomain::TYPE_PRIMARY, $siteId = null) {
        $this->domainName = $domainName;
        $this->type = $type;
        $this->siteId = $siteId;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getSiteId() {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     */
    public function setSiteId($siteId) {
        $this->siteId = $siteId;
    }

    /**
     * @return string
     */
    public function getDomainName() {
        return $this->domainName;
    }

    /**
     * @param string $domainName
     */
    public function setDomainName($domainName) {
        $this->domainName = $domainName;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }


}
