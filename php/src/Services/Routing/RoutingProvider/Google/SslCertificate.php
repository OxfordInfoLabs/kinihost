<?php

namespace Kinihost\Services\Routing\RoutingProvider\Google;

class SslCertificate extends \Google_Service_Compute_SslCertificate {

    public $managed;

    public $type;

    /**
     * @return mixed
     */
    public function getManaged() {
        return $this->managed;
    }

    /**
     * @param mixed $managed
     */
    public function setManaged($managed) {
        $this->managed = $managed;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type) {
        $this->type = $type;
    }


}
