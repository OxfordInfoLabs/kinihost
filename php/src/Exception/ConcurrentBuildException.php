<?php

namespace Kinihost\Exception;


/**
 * Exception raised if an attempt to perform two builds for the same site at the same time.
 *
 * Class ConcurrentBuildException
 */
class ConcurrentBuildException extends \Exception {

    public function __construct($siteKey) {
        parent::__construct("There is already a build in progress for the site with key $siteKey");
    }

}
