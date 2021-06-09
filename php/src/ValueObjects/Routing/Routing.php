<?php


namespace Kinihost\ValueObjects\Routing;

/**
 * Returned from a routing provider - extends the basic config object with
 * ipv4 / cname information for this routing.
 *
 * Class Routing
 * @package Kinihost\ValueObjects\Routing
 */
class Routing extends RoutingConfig {

    /**
     * @var RoutingBackendDNSSettings[]
     */
    private $backendDNSMappings;

    /**
     * Routing constructor.
     *
     * @param string $identifier
     * @param RoutingBackend[] $backends
     * @param string $ipv4Address
     * @param string $cName
     */
    public function __construct($identifier, $backends, $backendDNSSettings) {
        parent::__construct($identifier, $backends);
        $this->backendDNSMappings = $backendDNSSettings;

    }

    /**
     * Get the backend DNS mappings
     *
     * @return RoutingBackendDNSSettings[]
     */
    public function getBackendDNSMappings() {
        return $this->backendDNSMappings;
    }


}
