<?php

namespace Kinihost\ValueObjects\DNS;

class DNSRecord {


    /**
     * Name for this DNS record
     *
     * @var string
     */
    private $name;


    /**
     * Type e.g. A, MX etc
     *
     * @var string
     */
    private $type;


    /**
     * Value specific to the record type.
     *
     * @var mixed
     */
    private $recordData;


    /**
     * Time to Live
     *
     * @var integer
     */
    private $ttl;

    /**
     * DNSRecord constructor.
     *
     * @param string $name
     * @param string $type
     * @param mixed $recordData
     * @param int $ttl
     */
    public function __construct($name, $type, $recordData, $ttl = 3600) {
        $this->name = $name;
        $this->type = $type;
        $this->recordData = $recordData;
        $this->ttl = $ttl;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getRecordData() {
        return $this->recordData;
    }

    /**
     * @return int
     */
    public function getTtl() {
        return $this->ttl;
    }


}
