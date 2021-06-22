<?php


namespace Kinihost\Controllers\CLI;


use Kiniauth\Services\Account\UserService;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Security\Hash\SHA512HashProvider;
use Kinihost\ValueObjects\Auth\NewUserAccessTokenDescriptor;

class Auth {

    protected $userService;

    protected $session;

    /**
     * @param UserService $userService
     * @param Session $session
     */
    public function __construct($userService, $session) {
        $this->userService = $userService;
        $this->session = $session;
    }


    /**
     * Create a new user access token
     *
     * @http POST /accessToken
     *
     * @param NewUserAccessTokenDescriptor $newUserAccessToken
     */
    public function createUserAccessToken($newUserAccessToken) {

        // Salt the password to get through authentication security
        $password = $newUserAccessToken->getPassword();
        $hashProvider = new SHA512HashProvider();
        $password = $hashProvider->generateHash($password . $this->session->__getSessionSalt());

        $token = $this->userService->createUserAccessToken($newUserAccessToken->getEmailAddress(),
            $password, $newUserAccessToken->getTwoFactorCode());
        $this->userService->addSecondaryTokenToUserAccessToken($token, $newUserAccessToken->getSecondaryToken());
        return $token;
    }


}
