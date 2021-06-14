<?php

namespace Kinihost\ValueObjects\Build;

use Kinihost\Objects\Build\Build;

/**
 * Value object returned when a set of changed files is supplied for upload from the CLI.  This contains the
 * build id and an array of direct upload URLs - keyed in by original paths
 * where the source for each file should be uploaded individually.
 *
 * Class SourceUploadBuild
 */
class SourceUploadBuild {

    /**
     * @var integer
     */
    private $buildId;

    /**
     * @var integer
     */
    private $siteBuildNumber;


    /**
     * @var string[string]
     */
    private $uploadUrls;

    /**
     * SourceUploadDescriptor constructor.
     * @param Build $build
     * @param string $uploadUrls
     */
    public function __construct($build, $uploadUrls) {
        $this->buildId = $build->getId();
        $this->siteBuildNumber = $build->getSiteBuildNumber();
        $this->uploadUrls = $uploadUrls;
    }

    /**
     * @return int
     */
    public function getBuildId() {
        return $this->buildId;
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
    public function getUploadUrls() {
        return $this->uploadUrls;
    }


}
