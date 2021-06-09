<?php


namespace Kinihost\ValueObjects\Storage;


class ChangeResult {

    /**
     * @var string[]
     */
    private $created;

    /**
     * @var string[]
     */
    private $updated;

    /**
     * @var string[]
     */
    private $deleted;

    /**
     * @var string[string]
     */
    private $failed;


    // Failures
    const FAILED_DELETE_NOT_FOUND = "Delete failed as object not found";


    /**
     * ChangeResult constructor.
     *
     * @param string[] $created
     * @param string[] $updated
     * @param string[] $deleted
     * @param string[string] $failed
     */
    public function __construct($created, $updated, $deleted, $failed) {
        $this->created = $created;
        $this->updated = $updated;
        $this->deleted = $deleted;
        $this->failed = $failed;
    }


    /**
     * @return string[]
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * @return string[]
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * @return string[]
     */
    public function getDeleted() {
        return $this->deleted;
    }

    /**
     * @return string
     */
    public function getFailed() {
        return $this->failed;
    }


}
