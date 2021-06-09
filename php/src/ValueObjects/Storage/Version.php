<?php


namespace Kinihost\ValueObjects\Storage;


class Version {

    /**
     * @var integer
     */
    private $version;

    /**
     * @var \DateTime
     */
    private $createdDateTime;

    /**
     * Version constructor.
     * @param int $version
     * @param \DateTime $created
     */
    public function __construct($version, $created) {
        $this->version = $version;
        $this->createdDateTime = $created;
    }


    /**
     * @return int
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getCreatedDateTime() {
        return $this->createdDateTime ? $this->createdDateTime->format("d/m/Y H:i:s") : "";
    }


}
