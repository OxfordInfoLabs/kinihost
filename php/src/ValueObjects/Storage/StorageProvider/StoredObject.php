<?php


namespace Kinihost\ValueObjects\Storage\StorageProvider;


class StoredObject extends StoredObjectSummary {

    /**
     * Object content
     *
     * @var string
     */
    private $content;

    /**
     * Construct with all fields and content.
     *
     * StoredObject constructor.
     *
     * @param $containerKey
     * @param $key
     * @param $contentType
     * @param $size
     * @param \DateTime $createdTime
     * @param \DateTime $lastModifiedTime
     * @param $content
     */
    public function __construct($containerKey, $key, $contentType, $size, $createdTime, $lastModifiedTime, $content) {
        parent::__construct($containerKey, $key, $contentType, $size, $createdTime, $lastModifiedTime);
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }


}
