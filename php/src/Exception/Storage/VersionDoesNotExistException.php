<?php

namespace Kinihost\Exception\Storage;


class VersionDoesNotExistException extends \Exception {

    public function __construct($versionNumber) {
        parent::__construct("The previous version with number $versionNumber does not exist");
    }

}
