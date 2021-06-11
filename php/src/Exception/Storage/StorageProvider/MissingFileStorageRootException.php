<?php

namespace Kinihost\Exception\Storage\StorageProvider;


use Throwable;

class MissingFileStorageRootException extends \Exception {

    public function __construct() {
        parent::__construct("You must supply a file root explicitly or via configuration for the file storage provider");
    }

}
