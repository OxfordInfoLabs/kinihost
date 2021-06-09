<?php


namespace Kinihost\ValueObjects\Datastore;


class DatastoreQuery {

    /**
     * @var DatastoreFilter[]
     */
    private $filters = [];


    /**
     * @var DatastoreOrdering[]
     */
    private $orderings = [];


    /**
     * @var int
     */
    private $limit = -1;


    /**
     * @var int
     */
    private $offset = 0;

    /**
     * DatastoreQuery constructor.
     *
     * @param DatastoreFilter[] $filters
     * @param DatastoreOrdering[] $orderings
     * @param int $limit
     * @param int $offset
     */
    public function __construct($filters = [], $orderings = [], $limit = -1, $offset = 0) {
        $this->filters = $filters;
        $this->orderings = $orderings;
        $this->limit = $limit;
        $this->offset = $offset;
    }


    /**
     * @return DatastoreFilter[]
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * @param DatastoreFilter[] $filters
     */
    public function setFilters($filters) {
        $this->filters = $filters;
    }

    /**
     * @return DatastoreOrdering[]
     */
    public function getOrderings() {
        return $this->orderings;
    }

    /**
     * @param DatastoreOrdering[] $orderings
     */
    public function setOrderings($orderings) {
        $this->orderings = $orderings;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit) {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset) {
        $this->offset = $offset;
    }


}
