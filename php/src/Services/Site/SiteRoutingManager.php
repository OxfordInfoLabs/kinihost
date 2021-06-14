<?php


namespace Kinihost\Services\Site;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinihost\Services\Routing\RoutingProvider\RoutingProvider;
use Kinihost\ValueObjects\Routing\RoutingBackend;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\Objects\Site\Site;
use Kinihost\ValueObjects\Site\SiteRouting;

/**
 * Handles routing of sites using the common routing components.
 *
 * Class SiteRoutingManager
 * @package Kinihost\Services\Site
 */
class SiteRoutingManager {

    /**
     * Create all routing entries for this site.
     *
     * @param Site $site
     * @return SiteRouting
     */
    public function createRouting($site) {

        // Gather bits we need
        list($routingProviderKey, $serviceDomain, $provider) = $this->getRoutingProvider($site);

        // Generate a routing config from the site and service domain.
        $routingConfig = $this->generateRoutingConfigFromSite($site, $serviceDomain);

        return new SiteRouting($provider->createRouting($routingConfig), $routingProviderKey, $serviceDomain);
    }


    /**
     * Update routing for the passed site
     *
     * @param Site $site
     * @return SiteRouting
     */
    public function updateRouting($site) {

        // Gather provider bits
        list($routingProviderKey, $serviceDomain, $provider) = $this->getRoutingProvider($site);

        // Generate a routing config
        $routingConfig = $this->generateRoutingConfigFromSite($site, $site->getServiceDomain() ?? $serviceDomain);


        return new SiteRouting($provider->updateRouting($routingConfig), $routingProviderKey, $serviceDomain);
    }


    /**
     * Destroy the routing for the supplied site.
     *
     * @param Site $site
     */
    public function destroyRouting($site) {

    }


    /**
     * Get the routing status for a site from the routing provider
     *
     * @param $site
     */
    public function getRoutingStatus($site) {

        // Gather provider bits
        list($routingProviderKey, $serviceDomain, $provider) = $this->getRoutingProvider($site);

        // Generate a routing config
        $routingConfig = $this->generateRoutingConfigFromSite($site, $site->getServiceDomain());

        return $provider->getRoutingStatus($routingConfig);
    }

    /**
     * @param $site
     * @param $serviceDomain
     * @return RoutingConfig
     */
    private function generateRoutingConfigFromSite($site, $serviceDomain) {
        $previewDomain = $site->getSiteKey() . "-preview." . $serviceDomain;

        $backends = [
            new RoutingBackend($previewDomain, [], [$previewDomain])
        ];


        // If we are of type site, add production config.
        $productionBackend = $site->getProviderSettings()["primaryDomain"] ?? $site->getSiteKey() . "-production." . $serviceDomain;
        $productionDomains = ObjectArrayUtils::getMemberValueArrayForObjects("domainName", $site->getSiteDomains());
        $backends[] = new RoutingBackend($productionBackend, [], $productionDomains);

        // if we don't have a match for the production domain we add in the default backend.
        if (sizeof($productionDomains) > 1 || $productionDomains[0] != $productionBackend) {
            $unresolvedBackend = "unresolved-cname.$serviceDomain";
            $backends[] = new RoutingBackend($unresolvedBackend, [], [], true);
        }


        $routingConfig = new RoutingConfig($site->getSiteKey(), $backends);
        return $routingConfig;
    }


    /**
     * Get the routing provider for the passed site
     *
     * @param Site $site
     * @return RoutingProvider
     */
    private function getRoutingProvider($site) {
        $key = $site->getRoutingProviderKey() ?? Configuration::readParameter("kinihost.routing.provider");
        $serviceDomain = Configuration::readParameter("kinihost.service.domain");
        return [$key, $serviceDomain, Container::instance()->getInterfaceImplementation(RoutingProvider::class, $key)];
    }


}
