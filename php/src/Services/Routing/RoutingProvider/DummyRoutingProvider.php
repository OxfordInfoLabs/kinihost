<?php


namespace Kinihost\Services\Routing\RoutingProvider;


use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingBackendDNSSettings;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\ValueObjects\Routing\Status\CNameStatus;
use Kinihost\ValueObjects\Routing\Status\RoutingStatus;

/**
 * Dummy routing provider, mostly for testing
 *
 * Class DummyRoutingProvider
 * @package Kinihost\Services\Routing\RoutingProvider
 */
class DummyRoutingProvider implements RoutingProvider {

    /**
     * Create a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function createRouting($routing) {
        return new Routing($routing->getIdentifier(), $routing->getBackends(), $this->createBackendDNS($routing->getBackends()));
    }

    /**
     * Update a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function updateRouting($routing) {
        return new Routing($routing->getIdentifier(), $routing->getBackends(), $this->createBackendDNS($routing->getBackends()));
    }

    /**
     * Get a routing from the provider for the supplied identifier
     *
     * @param $identifier
     * @return Routing
     */
    public function getRouting($identifier) {
        return new Routing($identifier, [], []);
    }

    /**
     * Remove the routing with the supplied identifier.
     *
     * @param $identifier
     */
    public function removeRouting($identifier) {
    }

    /**
     * Return a routing status object based upon a config object.
     * Used to monitor / verify status of routing.
     *
     * @param RoutingConfig $routingConfig
     * @return RoutingStatus
     */
    public function getRoutingStatus($routingConfig) {

        $cNames = [];

        foreach ($routingConfig->getBackends() as $backend) {
            foreach ($backend->getInsecureCNames() as $secureCName) {
                $cNames[] = new CNameStatus($secureCName, true, 200,
                    200);
            }
        }

        return new RoutingStatus($cNames);
    }


    private function createBackendDNS($backends) {
        $backendDNS = [];
        foreach ($backends as $backend) {
            $backendDNS[$backend->getBackendReference()] = new RoutingBackendDNSSettings($backend->getBackendReference(), "0.0.0.0");
        }
        return $backendDNS;
    }
}
