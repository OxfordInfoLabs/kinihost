<?php

namespace Kinihost\Services\DNS\DNSProvider;

use Google_Client;
use Kinikit\Core\Configuration\Configuration;
use Kinihost\Services\Routing\RoutingProvider\Google\Compute;
use Kinihost\ValueObjects\DNS\DNSRecord;

class GoogleCloudDNSProvider implements DNSProvider {

    /**
     * @var \Google_Service_Dns $service
     */
    private $service;

    public function __construct() {

        putenv("GOOGLE_APPLICATION_CREDENTIALS=" . Configuration::readParameter("google.keyfile.path"));
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');

        $this->service = new \Google_Service_Dns($client);
        $this->projectId = Configuration::readParameter("google.project.id");

    }

    /**
     * Add a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function addDNSRecords($zoneIdentifier, $dnsRecords) {
        $dnsChange = new \Google_Service_Dns_Change();

        $additions = [];
        foreach ($dnsRecords as $dnsRecord) {

            $newRecordSet = new \Google_Service_Dns_ResourceRecordSet();
            $newRecordSet->setName(rtrim($dnsRecord->getName(), ".") . ".");
            $newRecordSet->setType($dnsRecord->getType());
            $newRecordSet->setRrdatas($dnsRecord->getRecordData());
            $newRecordSet->setTtl($dnsRecord->getTtl());

            $additions[] = $newRecordSet;

        }


        $dnsChange->setAdditions($additions);

        // Add the A record.
        $this->service->changes->create($this->projectId, $this->getZoneNameFromDomainName($zoneIdentifier), $dnsChange);
    }


    /**
     * Get all DNS records for a zone, optionally restricted to a name
     *
     * @param string $zoneIdentifier
     * @param string $name
     *
     * @return DNSRecord[]
     */
    public function getDNSRecords($zoneIdentifier, $name = null, $type = null) {

        $recordSets = $this->service->resourceRecordSets->listResourceRecordSets($this->projectId, $this->getZoneNameFromDomainName($zoneIdentifier))->getRrsets();

        $dnsRecords = [];

        // Loop through each record set.
        foreach ($recordSets as $recordSet) {

            $trimmedRecordName = rtrim($recordSet->getName(), ".");

            if ((!$name || $name == $trimmedRecordName) && (!$type || $type == $recordSet->getType())) {
                $recordData = $recordSet->getRrDatas();
                $dnsRecords[] = new DNSRecord($trimmedRecordName, $recordSet->getType(), $recordData, $recordSet->getTtl());
            }

        }

        return $dnsRecords;


    }


    /**
     * Delete a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function deleteDNSRecords($zoneIdentifier, $dnsRecords) {

        $dnsChange = new \Google_Service_Dns_Change();

        $deletions = [];
        foreach ($dnsRecords as $dnsRecord) {

            $newRecordSet = new \Google_Service_Dns_ResourceRecordSet();
            $newRecordSet->setName(rtrim($dnsRecord->getName(), ".") . ".");
            $newRecordSet->setType($dnsRecord->getType());
            $newRecordSet->setRrdatas($dnsRecord->getRecordData());
            $newRecordSet->setTtl($dnsRecord->getTtl());

            $deletions[] = $newRecordSet;
        }

        $dnsChange->setDeletions($deletions);

        // Delete the matching A record.
        $this->service->changes->create($this->projectId, $this->getZoneNameFromDomainName($zoneIdentifier), $dnsChange);

    }


    // Get the zone identifier from a domain name
    private function getZoneNameFromDomainName($domainName) {
        $zones = $this->service->managedZones->listManagedZones($this->projectId, ["dnsName" => $domainName . "."])->getManagedZones();
        return $zones[0]->getName();
    }
}
