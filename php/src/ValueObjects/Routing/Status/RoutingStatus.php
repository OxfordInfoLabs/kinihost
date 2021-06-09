<?php


namespace Kinihost\ValueObjects\Routing\Status;


class RoutingStatus {

    /**
     * @var CNameStatus[]
     */
    private $cNameStati;

    /**
     * RoutingStatus constructor.
     *
     * @param CNameStatus[] $cNameStati
     */
    public function __construct($cNameStati) {
        $this->cNameStati = $cNameStati;
    }


    /**
     * @return CNameStatus[]
     */
    public function getCNameStati() {
        return $this->cNameStati;
    }


    /**
     * @return boolean
     */
    public function isValid() {
        $valid = true;
        foreach ($this->cNameStati as $stati) {
            $valid = $valid && $stati->isValid();
        }

        return $valid;
    }


}
