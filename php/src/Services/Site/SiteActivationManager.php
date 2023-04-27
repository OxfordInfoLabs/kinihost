<?php


namespace Kinihost\Services\Site;


use Kinihost\Objects\Site\Site;
use Kinihost\ValueObjects\Site\SiteActivationStatus;

/**
 * Activation manager, handles activation of a site and provides
 * functionality to confirm that a site is active.
 *
 * Class SiteActivationManager
 * @package Kinihost\Services\Site
 */
class SiteActivationManager {

    /**
     * @var SiteStorageManager
     */
    private $storageManager;


    /**
     * @var SiteRoutingManager
     */
    private $routingManager;

    /**
     * @var SiteDNSManager
     */
    private $dnsManager;


    /**
     * @var SiteSourceService
     */
    private $sourceService;


    /**
     * SiteActivationManager constructor.
     *
     * @param SiteStorageManager $storageManager
     * @param SiteRoutingManager $routingManager
     * @param SiteDNSManager $dnsManager
     * @param SiteSourceService $sourceService
     */
    public function __construct($storageManager, $routingManager, $dnsManager, $sourceService) {
        $this->storageManager = $storageManager;
        $this->routingManager = $routingManager;
        $this->dnsManager = $dnsManager;
        $this->sourceService = $sourceService;
    }


    /**
     * Activate a site using the storage, routing and DNS managers
     *
     * @param Site $site
     */
    public function activateSite($site) {

        // Create storage for the site
        $storageProviderKey = $this->storageManager->createStorage($site);
        $site->setStorageProviderKey($storageProviderKey);

        // Initialise blank content
        $this->sourceService->installBlankContent($site);

        // Create routing for the site
        $routing = $this->routingManager->createRouting($site);
        $site->setRoutingProviderKey($routing->getRoutingProviderKey());
        $site->setServiceDomain($routing->getServiceDomain());

        // Create DNS entries
        $dnsKey = $this->dnsManager->createServiceDNSForSite($site, $routing);
        $site->setDnsProviderKey($dnsKey);


    }


    /**
     * Get the activation status for a site
     *
     * @param $site
     */
    public function getActivationStatus($site) {

        // Grab the bits we need
        $routingStatus = $this->routingManager->getRoutingStatus($site);


        return new SiteActivationStatus($site->getSiteKey(), $routingStatus);
    }


}
