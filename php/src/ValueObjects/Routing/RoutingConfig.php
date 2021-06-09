<?php

namespace Kinihost\ValueObjects\Routing;

/**
 * Routing config, passed to a routing provider.
 *
 * Class RoutingConfig
 */
class RoutingConfig {

    /**
     * An identifier for this routing config - this often maps to a unique key for the
     * site / object being mapped.
     *
     * @var string
     */
    private $identifier;


    /**
     * Set of backends for this routing
     *
     * @var RoutingBackend[]
     */
    private $backends;


    /**
     * Construct with the required items.
     *
     * @param string $identifier
     * @param RoutingBackend[] $backends
     */
    public function __construct($identifier, $backends) {
        $this->identifier = $identifier;
        $this->backends = $backends;
    }


    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return RoutingBackend[]
     */
    public function getBackends() {
        return $this->backends;
    }

    /**
     * Return indicator as to whether this routing config contains any insecure CNames
     *
     * @return bool
     */
    public function hasInsecureCNames() {
        foreach ($this->backends as $backend) {
            if (sizeof($backend->getInsecureCNames()) > 0)
                return true;
        }

        return false;
    }


    /**
     * Return indicator as to whether this routing config contains any secure CNames
     *
     * @return bool
     */
    public function hasSecureCNames() {
        foreach ($this->backends as $backend) {
            if (sizeof($backend->getSecureCNames()) > 0)
                return true;
        }

        return false;
    }


}
