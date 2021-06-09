<?php


namespace Kinihost\ValueObjects\Storage;


class VersionRestoreChangedObject {

    /**
     * The object key being restored
     *
     * @var string
     */
    private $objectKey;


    /**
     * The change type (UPDATE / DELETE)
     *
     * @var string
     */
    private $changeType;


    /**
     * Which version to visit to find the new version
     * of this file if an UPDATE
     *
     * @var integer
     */
    private $version;

    /**
     * VersionRestoreChangedObject constructor.
     *
     * @param string $objectKey
     * @param string $changeType
     * @param int $version
     */
    public function __construct($objectKey, $changeType, $version = null) {
        $this->objectKey = $objectKey;
        $this->changeType = $changeType;
        $this->version = $version;
    }


    /**
     * @return string
     */
    public function getObjectKey() {
        return $this->objectKey;
    }

    /**
     * @return string
     */
    public function getChangeType() {
        return $this->changeType;
    }

    /**
     * @return int
     */
    public function getVersion() {
        return $this->version;
    }


}
