<?php

namespace Kinihost\ValueObjects\Auth;

class NewUserAccessTokenDescriptor extends \Kiniauth\ValueObjects\Security\NewUserAccessTokenDescriptor {

    /**
     * @var string
     */
    private $secondaryToken;

    /**
     * @return string
     */
    public function getSecondaryToken() {
        return $this->secondaryToken;
    }

    /**
     * @param string $secondaryToken
     */
    public function setSecondaryToken($secondaryToken) {
        $this->secondaryToken = $secondaryToken;
    }


}
