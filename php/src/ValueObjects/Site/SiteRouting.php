<?php


namespace Kinihost\ValueObjects\Site;


use Kinihost\ValueObjects\Routing\Routing;

/**
 * Extension of the routing class which also encodes the routing provider key
 *
 * Class SiteRouting
 * @package Kinihost\ValueObjects\Site
 */
class SiteRouting extends Routing {

    /**
     * @var string
     */
    private $routingProviderKey;

    /**
     * @var string
     */
    private $serviceDomain;


    /**
     * SiteRouting constructor.
     *
     * @param Routing $routing
     * @param string $routingProviderKey
     */
    public function __construct($routing, $routingProviderKey, $serviceDomain) {
        parent::__construct($routing->getIdentifier(), $routing->getBackends(), $routing->getBackendDNSMappings());
        $this->routingProviderKey = $routingProviderKey;
        $this->serviceDomain = $serviceDomain;
    }

    /**
     * @return mixed
     */
    public function getRoutingProviderKey() {
        return $this->routingProviderKey;
    }

    /**
     * @return string
     */
    public function getServiceDomain() {
        return $this->serviceDomain;
    }


}
