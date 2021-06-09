<?php

namespace Kinihost\ValueObjects\Datastore;

class DatastoreFilter {

    /**
     * Which member is being filtered on.
     *
     * @var string
     */
    private $filterMember;

    /**
     * One of the type constants available in this class
     *
     * @var string
     */
    private $filterType;

    /**
     * The filter value to use for filtering
     *
     * @var mixed
     */
    private $filterValue;


    const EQUALS = "==";
    const GREATER_THAN = ">";
    const LESS_THAN = "<";
    const GREATER_THAN_EQUALS = ">=";
    const LESS_THAN_EQUALS = "<=";
    const IN = "IN";
    const CONTAINS = "CONTAINS";
    const LIKE = "LIKE";


    /**
     * DataStoreFilter constructor.
     *
     * @param string $filterMember
     * @param string $filterType
     * @param mixed $filterValue
     */
    public function __construct($filterMember, $filterValue, $filterType = self::EQUALS) {
        $this->filterMember = $filterMember;
        $this->filterType = $filterType;
        $this->filterValue = $filterValue;
    }


    /**
     * @return string
     */
    public function getFilterMember() {
        return $this->filterMember;
    }

    /**
     * @param string $filterMember
     */
    public function setFilterMember($filterMember) {
        $this->filterMember = $filterMember;
    }

    /**
     * @return string
     */
    public function getFilterType() {
        return $this->filterType;
    }

    /**
     * @param string $filterType
     */
    public function setFilterType($filterType) {
        $this->filterType = $filterType;
    }

    /**
     * @return mixed
     */
    public function getFilterValue() {
        return $this->filterValue;
    }

    /**
     * @param mixed $filterValue
     */
    public function setFilterValue($filterValue) {
        $this->filterValue = $filterValue;
    }


}
