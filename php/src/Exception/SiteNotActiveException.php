<?php


namespace Kinihost\Exception;


class SiteNotActiveException extends \Exception {

    public function __construct($siteKey) {
        parent::__construct("The site with key $siteKey has not yet been activated");
    }

}