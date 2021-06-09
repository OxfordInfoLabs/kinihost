<?php

namespace Kinihost\ValueObjects\Storage\StorageProvider;

class StoredObjectSummary {


    /**
     * @var string
     */
    private $containerKey;

    /**
     * @var string
     */
    private $key;


    /**
     * @var string
     */
    private $contentType;


    /**
     * @var integer
     */
    private $size;

    /**
     * @var \DateTime
     */
    private $createdTime;

    /**
     * @var \DateTime
     */
    private $lastModifiedTime;


    /**
     * StoredObjectSummary constructor.
     * @param string $containerKey
     * @param string $key
     * @param string $contentType
     * @param int $size
     * @param \DateTime $createdTime
     * @param \DateTime $lastModifiedTime
     */
    public function __construct($containerKey, $key, $contentType, $size, $createdTime, $lastModifiedTime) {
        $this->containerKey = $containerKey;
        $this->key = $key;
        $this->contentType = $contentType;
        $this->size = $size;
        $this->createdTime = $createdTime;
        $this->lastModifiedTime = $lastModifiedTime;
    }


    /**
     * @return string
     */
    public function getContainerKey() {
        return $this->containerKey;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * Get the parent folder (for listing GUI purposes)
     *
     * @return string
     */
    public function getParentFolder() {
        $explodedKey = explode("/", $this->getKey());
        array_pop($explodedKey);
        return join("/", $explodedKey);
    }

    /**
     * Get the leaf name (for listing GUI purposes)
     *
     * @return string
     */
    public function getLeafName() {
        $explodedKey = explode("/", $this->getKey());
        return array_pop($explodedKey);
    }

    /**
     * @return string
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedTime() {
        return $this->createdTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastModifiedTime() {
        return $this->lastModifiedTime;
    }


}
