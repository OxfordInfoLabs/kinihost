<?php

namespace Kinihost;

use Kiniauth\Services\Security\ScopeManager;
use Kinihost\Services\Security\CLIRouteInterceptor;
use Kinikit\Core\ApplicationBootstrap;
use Kinihost\Services\Security\SiteScopeAccess;
use Kinikit\MVC\Routing\RouteInterceptorProcessor;

/**
 * Inject core functionality required by Oxford Cyber.
 */
class Bootstrap implements ApplicationBootstrap {


    /**
     * @var ScopeManager
     */
    private $scopeManager;


    /**
     * @var RouteInterceptorProcessor
     */
    private $routeInterceptorProcessor;

    /**
     * Bootstrap constructor.
     *
     * @param ScopeManager $scopeManager
     * @param RouteInterceptorProcessor $routeInterceptorProcessor
     */
    public function __construct($scopeManager, $routeInterceptorProcessor) {
        $this->scopeManager = $scopeManager;
        $this->routeInterceptorProcessor = $routeInterceptorProcessor;
    }

    /**
     * Set up logic, run on each request, first before any request processing.
     *
     */
    public function setup() {

        // Add the site scope access.
        $this->scopeManager->addScopeAccess(new SiteScopeAccess());

        // Add the CLI route interceptor
        $this->routeInterceptorProcessor->addInterceptor("cli/*", CLIRouteInterceptor::class);
    }
}
