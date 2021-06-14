<?php

namespace Kinihost\Services\Security;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\Session;
use Kiniauth\Services\Security\AuthenticationService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinihost\TestBase;

include_once "autoloader.php";

class CLIRouteInterceptorTest extends TestBase {


    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var Session
     */
    private $session;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->session = Container::instance()->get(Session::class);
    }

    public function testIfNoUserAccessAndSecondaryTokensPassedAsRequestParametersAccessDeniedExceptionThrown() {


        $cliRouteInterceptor = new CLIRouteInterceptor($this->authenticationService);

        try {
            $cliRouteInterceptor->beforeRoute(new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }

    }


    public function testIfBadAccessAndSecondaryTokensPassedAsRequestParametersAccessDeniedExceptionThrown() {


        $cliRouteInterceptor = new CLIRouteInterceptor($this->authenticationService);

        $_GET["userAccessToken"] = "BAD_TOKEN";
        $_GET["secondaryToken"] = "BAD_TOKEN";

        $request = new Request(new Headers());

        try {
            $cliRouteInterceptor->beforeRoute($request);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }


    }


    public function testIfValidAccessAndSecondaryTokensPassedAsRequestParametersRequestIsAllowedThroughAndUserIsLoggedIn() {


        // Logout first
        $this->authenticationService->logout();

        $cliRouteInterceptor = new CLIRouteInterceptor($this->authenticationService);

        $_GET["userAccessToken"] = "TESTTOKEN2";
        $_GET["secondaryToken"] = "TESTSECONDARY";

        $request = new Request(new Headers());

        // Should succeed
        $cliRouteInterceptor->beforeRoute($request);

        // Should now be logged in.
        $this->assertEquals(7, $this->session->__getLoggedInUser()->getId());

    }


}
