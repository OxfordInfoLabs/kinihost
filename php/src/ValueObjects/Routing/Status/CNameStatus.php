<?php


namespace Kinihost\ValueObjects\Routing\Status;


class CNameStatus {

    /**
     * The CName
     *
     * @var string
     */
    private $cName;


    /**
     * @var boolean
     */
    private $secure;

    /**
     * @var integer
     */
    private $secureResponseCode;

    /**
     * The status code when this is accessed
     *
     * @var integer
     */
    private $insecureResponseCode;

    /**
     * The redirection domain for this CName if a redirect occurred.
     *
     * @var string
     */
    private $insecureRedirectionUrl;

    /**
     * CNameStatus constructor.
     * @param string $cName
     * @param boolean $secure
     * @param bool $secureResponseCode
     * @param int $insecureResponseCode
     * @param string $insecureRedirectionDomain
     */
    public function __construct($cName, $secure, $secureResponseCode, $insecureResponseCode, $insecureRedirectionDomain = "") {
        $this->cName = $cName;
        $this->secure = $secure;
        $this->secureResponseCode = $secureResponseCode;
        $this->insecureResponseCode = $insecureResponseCode;
        $this->insecureRedirectionUrl = $insecureRedirectionDomain;
    }


    /**
     * @return string
     */
    public function getCName() {
        return $this->cName;
    }

    /**
     * @return bool
     */
    public function isSecure() {
        return $this->secure;
    }

    /**
     * @return int
     */
    public function getSecureResponseCode() {
        return $this->secureResponseCode;
    }


    /**
     * @return int
     */
    public function getInsecureResponseCode() {
        return $this->insecureResponseCode;
    }

    /**
     * @return string
     */
    public function getInsecureRedirectionUrl() {
        return $this->insecureRedirectionUrl;
    }


    /**
     * Return an aggregated boolean indicating whether or not this CName is valid
     *
     * @return boolean
     */
    public function isValid() {

        $valid = true;

        if ($this->isSecure()) {
            $valid = $valid && ($this->secureResponseCode == 200) && ($this->insecureResponseCode >= 200) && ($this->insecureResponseCode < 303);
        } else {
            $valid = $this->insecureResponseCode == 200;
        }

        return $valid;
    }


}


