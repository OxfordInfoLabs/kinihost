<?php

namespace Kinihost\Services\Security;

use Kiniauth\Services\Security\AuthenticationService;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Routing\RouteInterceptor;

class CLIRouteInterceptor extends RouteInterceptor {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * Construct with auth service
     *
     * CLIRouteInterceptor constructor.
     *
     * @param AuthenticationService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
    }

    /**
     * Ensure all CLI routes are checked
     *
     * @param \Kinikit\MVC\Request\Request $request
     * @return \Kinikit\MVC\Response\Response|void|null
     */
    public function beforeRoute($request) {

        // Allow the auth segment
        if ($request->getUrl()->getPathSegment(1) == "auth")
            return;

        if ($request->getParameter("userAccessToken") && $request->getParameter("secondaryToken")) {

            try {
                $this->authenticationService->authenticateByUserToken($request->getParameter("userAccessToken"), $request->getParameter("secondaryToken"));
                return;
            } catch (\Exception $e) {
                // Ignore exceptions and fall through to throw.
            }
        }

        throw new AccessDeniedException("You must be authenticated with a valid user token to access the CLI APIs");
    }

}
