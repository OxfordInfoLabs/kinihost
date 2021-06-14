<?php

namespace Kinihost;

use Kiniauth\Services\Security\ScopeManager;
use Kinikit\Core\ApplicationBootstrap;
use Kinihost\Services\Security\SiteScopeAccess;

/**
 * Inject core functionality required by Oxford Cyber.
 */
class Bootstrap implements ApplicationBootstrap {


    /**
     * @var ScopeManager
     */
    private $scopeManager;


    /**
     * Bootstrap constructor.
     *
     * @param ScopeManager $scopeManager
     */
    public function __construct($scopeManager) {
        $this->scopeManager = $scopeManager;
    }

    /**
     * Set up logic, run on each request, first before any request processing.
     *
     */
    public function setup() {

        // Add the site scope access.
        $this->scopeManager->addScopeAccess(new SiteScopeAccess());
    }
}
