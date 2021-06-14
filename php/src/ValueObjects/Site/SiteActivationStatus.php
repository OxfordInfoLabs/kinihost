<?php


namespace Kinihost\ValueObjects\Site;


use Kinihost\ValueObjects\Routing\Status\RoutingStatus;

/**
 * Activation status for a site
 *
 * Class SiteActivationStatus
 * @package Kinihost\ValueObjects\Site
 */
class SiteActivationStatus {


    /**
     * @var string
     */
    private $siteKey;

    /**
     * @var RoutingStatus
     */
    private $routingStatus;

    /**
     * SiteActivationStatus constructor.
     *
     * @param string $siteKey
     * @param RoutingStatus $routingStatus
     */
    public function __construct($siteKey, $routingStatus) {
        $this->siteKey = $siteKey;
        $this->routingStatus = $routingStatus;
    }


    /**
     * @return int
     */
    public function getSiteKey() {
        return $this->siteKey;
    }

    /**
     * @return RoutingStatus
     */
    public function getRoutingStatus() {
        return $this->routingStatus;
    }


    /**
     * Global valid indicator
     *
     * @return bool
     */
    public function isValid() {
        return $this->routingStatus->isValid();
    }

}
