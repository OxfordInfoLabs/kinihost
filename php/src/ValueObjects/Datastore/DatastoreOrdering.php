<?php

namespace Kinihost\ValueObjects\Datastore;

class DatastoreOrdering {

    /**
     * The field to order by
     *
     * @var string
     */
    private $field;

    /**
     * One of the direction constants in this class
     *
     * @var string
     */
    private $direction;


    // Direction constants
    const ASCENDING = "ASC";
    const DESCENDING = "DESC";

    /**
     * DataStoreOrdering constructor.
     * @param string $field
     * @param string $direction
     */
    public function __construct($field, $direction = self::ASCENDING) {
        $this->field = $field;
        $this->direction = $direction;
    }


    /**
     * @return string
     */
    public function getField() {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field) {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getDirection() {
        return $this->direction;
    }

    /**
     * @param string $direction
     */
    public function setDirection($direction) {
        $this->direction = $direction;
    }


}
