<?php

namespace Kinihost\Services\Routing\RoutingProvider;

use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\ValueObjects\Routing\RoutingStatus;

/**
 * Routing provider base interface
 *
 * @implementation google Kinihost\Services\Routing\RoutingProvider\GoogleRoutingProvider
 * @implementation dummy Kinihost\Services\Routing\RoutingProvider\DummyRoutingProvider
 */
interface RoutingProvider {


    /**
     * Create a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function createRouting($routing);


    /**
     * Get a routing from the provider for the supplied identifier
     *
     * @param $identifier
     * @return Routing
     */
    public function getRouting($identifier);


    /**
     * Update a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function updateRouting($routing);


    /**
     * Remove the routing with the supplied identifier.
     *
     * @param $identifier
     */
    public function removeRouting($identifier);


    /**
     * Return a routing status object based upon a config object.
     * Used to monitor / verify status of routing.
     *
     * @param RoutingConfig $routingConfig
     * @return RoutingStatus
     */
    public function getRoutingStatus($routingConfig);


}

