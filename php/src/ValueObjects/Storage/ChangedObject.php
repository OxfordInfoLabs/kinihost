<?php

namespace Kinihost\ValueObjects\Storage;

/**
 * Changed object class - represents either a new object or a delete
 *
 * Class ChangedObject
 */
class ChangedObject {

    /**
     * Object key, relative to a target storage root.
     *
     * @var string
     */
    private $objectKey;


    /**
     * One of the change type constants below.
     *
     * @var string
     */
    private $changeType;


    /**
     * Explicit content for this object
     *
     * @var string
     */
    private $objectContent;


    /**
     * Local filename for this object
     *
     * @var string
     */
    private $localFilename;


    /**
     * MD5 of the content for optimisation
     *
     * @var string
     */
    private $md5Hash;


    // Change type constants.
    const CHANGE_TYPE_UPDATE = "UPDATE";
    const CHANGE_TYPE_DELETE = "DELETE";

    /**
     * ChangedObject type.
     *
     * @param string $objectKey
     * @param string $changeType
     * @param string $objectContent
     * @param string $localFilename
     */
    public function __construct($objectKey, $changeType, $objectContent = null, $localFilename = null, $md5Hash = null) {
        $this->objectKey = $objectKey;
        $this->changeType = $changeType;
        $this->objectContent = $objectContent;
        $this->localFilename = $localFilename;
        $this->md5Hash = $md5Hash;
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
     * @return string
     */
    public function getObjectContent() {
        return $this->objectContent;
    }

    /**
     * @return string
     */
    public function getLocalFilename() {
        return $this->localFilename;
    }

    /**
     * @return string
     */
    public function getMd5Hash() {
        return $this->md5Hash;
    }

    /**
     * @param string $objectKey
     */
    public function setObjectKey($objectKey) {
        $this->objectKey = $objectKey;
    }

    /**
     * @param string $changeType
     */
    public function setChangeType($changeType) {
        $this->changeType = $changeType;
    }

    /**
     * @param string $objectContent
     */
    public function setObjectContent($objectContent) {
        $this->objectContent = $objectContent;
    }

    /**
     * @param string $localFilename
     */
    public function setLocalFilename($localFilename) {
        $this->localFilename = $localFilename;
    }

    /**
     * @param string $md5Hash
     */
    public function setMd5Hash($md5Hash) {
        $this->md5Hash = $md5Hash;
    }


}
