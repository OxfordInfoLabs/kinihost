<?php


namespace Kinihost\ValueObjects\Site;


class SiteDescriptor {


    /**
     * The siteKey for this site create descriptor.
     *
     * @var string
     */
    protected $siteKey;
    /**
     * The title for this site create descriptor.
     *
     * @var string
     */
    protected $title;


    /**
     * SiteUpdateDescriptor constructor.
     * @param string $siteKey
     * @param string $title
     */
    public function __construct( $title = "", $siteKey = "") {
        $this->title = $title;
        $this->siteKey = $siteKey;
    }


    /**
     * @return string
     */
    public function getSiteKey() {
        return $this->siteKey;
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
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }


}