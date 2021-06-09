<?php


namespace Kinihost\ValueObjects\Routing;


class RoutingBackendDNSSettings {

    /**
     * @var string
     */
    private $backendReference;

    /**
     * @var string
     */
    private $ipAddress;


    /**
     * @var string
     */
    private $cName;

    /**
     * RoutingBackendDNSSettings constructor.
     *
     * @param string $backendReference
     * @param string $ipAddress
     * @param string $cName
     */
    public function __construct($backendReference, $ipAddress = null, $cName = null) {
        $this->backendReference = $backendReference;
        $this->ipAddress = $ipAddress;
        $this->cName = $cName;
    }


    /**
     * @return string
     */
    public function getBackendReference() {
        return $this->backendReference;
    }


    /**
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }


    /**
     * @return string
     */
    public function getCName() {
        return $this->cName;
    }


}
