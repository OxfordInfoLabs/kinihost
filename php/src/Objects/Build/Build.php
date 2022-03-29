<?php

namespace Kinihost\Objects\Build;

use DateTime;
use Kiniauth\Objects\Security\User;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinihost\ValueObjects\Storage\ChangedObject;

/**
 * Class Build
 *
 * @table kh_build
 * @generate
 */
class Build extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;


    /**
     * @var integer
     */
    private $accountId;


    /**
     * The user id who initiated the build if applicable.
     *
     * @var integer
     */
    private $initiatingUserId;


    /**
     * @var integer
     */
    private $siteId;


    /**
     * @var integer
     */
    private $siteBuildNumber;


    /**
     * @var string
     */
    private $buildType;


    /**
     * @var string
     */
    private $status;


    /**
     * Associated data specific to the build type if required.
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    private $data;

    /**
     * @var \DateTime
     */
    private $createdDate;


    /**
     * @var \DateTime
     */
    private $queuedDate;


    /**
     * @var \DateTime
     */
    private $startedDate;


    /**
     * @var \DateTime
     */
    private $completedDate;


    /**
     * @var string
     * @sqlType LONGTEXT
     */
    private $failureMessage;


    /**
     * @var User
     *
     * @manyToOne
     * @parentJoinColumns initiating_user_id
     * @readOnly
     */
    private $initiatingUser;


    // Build types
    const TYPE_SOURCE_UPLOAD = "SOURCE_UPLOAD";
    const TYPE_VERSION_REVERT = "VERSION_REVERT";
    const TYPE_CURRENT = "CURRENT";
    const TYPE_PREVIEW = "PREVIEW";
    const TYPE_PUBLISH = "PUBLISH";

    // Stati for build
    const STATUS_PENDING = "PENDING";
    const STATUS_QUEUED = "QUEUED";
    const STATUS_RUNNING = "RUNNING";
    const STATUS_FAILED = "FAILED";
    const STATUS_SUCCEEDED = "SUCCEEDED";


    /**
     * Constructor
     *
     * Build constructor.
     */
    public function __construct($site, $buildType, $status = self::STATUS_PENDING, $initiatingUserId = null, $data = []) {

        if ($site) {
            $this->siteId = $site->getSiteId();
            $this->accountId = $site->getAccountId();
            $this->siteBuildNumber = $site->getLastBuildNumber();
        }

        $this->buildType = $buildType;

        $this->initiatingUserId = $initiatingUserId;

        $this->status = $status;
        $this->data = $data;

        $this->createdDate = new \DateTime();

        if ($status == self::STATUS_QUEUED) {
            $this->queuedDate = new \DateTime();
        }

    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getInitiatingUserId() {
        return $this->initiatingUserId;
    }

    /**
     * @return User
     */
    public function getInitiatingUser() {
        return $this->initiatingUser;
    }


    /**
     * @return int
     */
    public function getSiteId() {
        return $this->siteId;
    }

    /**
     * @return int
     */
    public function getSiteBuildNumber() {
        return $this->siteBuildNumber;
    }

    /**
     * @return string
     */
    public function getBuildType() {
        return $this->buildType;
    }


    /**
     * @return ChangedObject[]
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param ChangedObject[] $data
     */
    public function setData($data) {
        $this->data = $data;
    }


    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * @return DateTime
     */
    public function getQueuedDate() {
        return $this->queuedDate;
    }

    /**
     * @return DateTime
     */
    public function getStartedDate() {
        return $this->startedDate;
    }

    /**
     * @return DateTime
     */
    public function getCompletedDate() {
        return $this->completedDate;
    }

    /**
     * @return string
     */
    public function getFailureMessage() {
        return $this->failureMessage;
    }


    /**
     * Register status changes
     *
     * @param $status
     * @param string $message
     */
    public function registerStatusChange($status, $message = null) {
        $this->status = $status;
        if ($this->status == self::STATUS_QUEUED) {
            $this->queuedDate = new \DateTime();
        } else if ($this->status == self::STATUS_RUNNING) {
            $this->startedDate = new \DateTime();
        } else {
            $this->completedDate = new \DateTime();
            if ($this->status == self::STATUS_FAILED) {
                $this->failureMessage = $message;
            }
        }

        // Save
        $this->save();
    }

}
