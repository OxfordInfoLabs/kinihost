<?php


namespace Kinihost\ValueObjects\Routing;


class RoutingBackend {


    /**
     * An implementation specific reference to the backend for this routing
     *
     * @var string
     */
    private $backendReference;


    /**
     * Array of mapped secure CNames for this backend routing
     *
     * @var string[]
     */
    private $secureCNames;


    /**
     * Array of insecure CNames for this backend routing.
     *
     * @var string[]
     */
    private $insecureCNames;


    /**
     * @var boolean
     */
    private $defaultBackend;


    /**
     * RoutingBackend constructor.
     *
     * @param string $backendReference
     * @param string[] $mappedCNames
     */
    public function __construct($backendReference, $secureCNames = [], $insecureCNames = [], $defaultBackend = false) {
        $this->backendReference = $backendReference;
        $this->secureCNames = $secureCNames;
        $this->insecureCNames = $insecureCNames;
        $this->defaultBackend = $defaultBackend;
    }


    /**
     * @return string
     */
    public function getBackendReference() {
        return $this->backendReference;
    }

    /**
     * @return string[]
     */
    public function getSecureCNames() {
        return $this->secureCNames;
    }

    /**
     * @return string[]
     */
    public function getInsecureCNames() {
        return $this->insecureCNames;
    }

    /**
     * @return bool
     */
    public function isDefaultBackend() {
        return $this->defaultBackend;
    }


}
