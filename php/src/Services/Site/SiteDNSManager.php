<?php


namespace Kinihost\Services\Site;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinihost\Services\DNS\DNSProvider\DNSProvider;
use Kinihost\ValueObjects\DNS\DNSRecord;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\Objects\Site\Site;

class SiteDNSManager {


    /**
     * Create service DNS entries for a site.  This uses both a site
     * and a routing object to determine the address / CName to use to map.
     *
     * @param Site $site
     * @param Routing $routing
     *
     */
    public function createServiceDNSForSite($site, $routing) {
        list($key, $serviceDomain, $dnsProvider) = $this->getDNSProvider($site);

        // Remove old preview records if any exist.
        $oldPreviewRecords = $dnsProvider->getDNSRecords($serviceDomain, $site->getSiteKey() . "-preview." . $serviceDomain);
        if (is_array($oldPreviewRecords) && sizeof($oldPreviewRecords))
            $dnsProvider->deleteDNSRecords($serviceDomain, $oldPreviewRecords);

        // Create preview record.
        $dnsMapping = $routing->getBackendDNSMappings()[$routing->getBackends()[0]->getBackendReference()];
        if ($dnsMapping->getCName()) {
            $previewRecord = new DNSRecord($site->getSiteKey() . "-preview." . $serviceDomain, "CNAME", $dnsMapping->getCName(), 0);
        } else {
            $previewRecord = new DNSRecord($site->getSiteKey() . "-preview." . $serviceDomain, "A", $dnsMapping->getIpAddress(), 0);
        }

        $records = [$previewRecord];


        $oldProductionRecords = $dnsProvider->getDNSRecords($serviceDomain, $site->getSiteKey() . "-production." . $serviceDomain);
        if (is_array($oldProductionRecords) && sizeof($oldProductionRecords))
            $dnsProvider->deleteDNSRecords($serviceDomain, $oldProductionRecords);

        // Create production record.
        $dnsMapping = $routing->getBackendDNSMappings()[$routing->getBackends()[1]->getBackendReference()];
        if ($dnsMapping->getCName()) {
            $productionRecord = new DNSRecord($site->getSiteKey() . "-production." . $serviceDomain, "CNAME", $dnsMapping->getCName(), 0);
        } else {
            $productionRecord = new DNSRecord($site->getSiteKey() . "-production." . $serviceDomain, "A", $dnsMapping->getIpAddress(), 0);
        }

        $records[] = $productionRecord;


        $dnsProvider->addDNSRecords($serviceDomain, $records);

        return $key;
    }


    /**
     * Activate maintenance DNS
     */
    public function activateMaintenanceDNS($site) {

        list($key, $serviceDomain, $dnsProvider) = $this->getDNSProvider($site);

        // Grab any existing production records and remove them
        $oldProductionRecords = $dnsProvider->getDNSRecords($serviceDomain, $site->getSiteKey() . "-production." . $serviceDomain);
        if (sizeof($oldProductionRecords))
            $dnsProvider->deleteDNSRecords($serviceDomain, $oldProductionRecords);

        // Apply the new record
        $maintenanceRecord = new DNSRecord($site->getSiteKey() . "-production." . $serviceDomain, "CNAME", "maintenance.$serviceDomain", 0);
        $dnsProvider->addDNSRecords($serviceDomain, [$maintenanceRecord]);

    }


    /**
     * Get the routing provider for the passed site
     *
     * @param Site $site
     * @return array(string,string,DNSProvider)
     */
    private function getDNSProvider($site) {
        $key = $site->getDnsProviderKey() ?? Configuration::readParameter("kinihost.dns.provider");
        $serviceDomain = $site->getServiceDomain() ?? Configuration::readParameter("kinihost.service.domain");
        return [$key, $serviceDomain, Container::instance()->getInterfaceImplementation(DNSProvider::class, $key)];
    }


}
