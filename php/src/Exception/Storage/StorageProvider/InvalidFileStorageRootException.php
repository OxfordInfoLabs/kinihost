<?php

namespace Kinihost\Exception\Storage\StorageProvider;


use Throwable;

class InvalidFileStorageRootException extends \Exception {

    public function __construct($storageRoot) {
        parent::__construct("The storage root $storageRoot does not point to a valid file directory");
    }

}
