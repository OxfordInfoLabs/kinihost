<?php


namespace Kinihost\Services\DNS\DNSProvider;


use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIKey;
use Cloudflare\API\Endpoints\Zones;
use Cloudflare\API\Endpoints\DNS;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\HTTP\HttpRemoteRequest;
use Kinikit\Core\HTTP\HttpRequestErrorException;
use Kinihost\ValueObjects\DNS\DNSRecord;

class CloudflareDNSProvider implements DNSProvider {


    /**
     * @var Guzzle
     */
    private $adapter;

    /**
     * @var DNS
     */
    private $dns;

    public function __construct() {
        $key = new APIKey(
            Configuration::readParameter("cloudflare.api.email"),
            Configuration::readParameter("cloudflare.api.key"));

        $this->adapter = new Guzzle($key);
        $this->dns = new DNS($this->adapter);
    }

    /**
     * Add a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function addDNSRecords($zoneIdentifier, $dnsRecords) {
        $zoneId = $this->getZoneId($zoneIdentifier);

        foreach ($dnsRecords as $record) {
            $this->dns->addRecord($zoneId, $record->getType(), $record->getName(), $record->getRecordData(), $record->getTtl(), true);
        }
    }

    /**
     * Get all DNS records for a zone, optionally restricted to a name
     *
     * @param string $zoneIdentifier
     * @param string $name
     *
     * @return DNSRecord[]
     */
    public function getDNSRecords($zoneIdentifier, $name = "", $type = "") {

        $zoneId = $this->getZoneId($zoneIdentifier);

        $records = $this->dns->listRecords($zoneId, $type, $name);

        $dnsRecords = [];
        foreach ($records->result as $record) {
            $dnsRecords[] = new DNSRecord($record->name, $record->type, [$record->content], $record->ttl);
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
        $zoneId = $this->getZoneId($zoneIdentifier);

        foreach ($dnsRecords as $record) {
            $recordData = is_array($record->getRecordData()) ? $record->getRecordData()[0] : $record->getRecordData();
            $dnsRecord = $this->dns->listRecords($zoneId, $record->getType(), $record->getName(), $recordData);
            if (sizeof($dnsRecord->result) > 0) {
                $this->dns->deleteRecord($zoneId, $dnsRecord->result[0]->id);
            }
        }

    }


    // Get the zone id
    private function getZoneId($domainName) {
        $zones = new Zones($this->adapter);
        return $zones->getZoneID($domainName);
    }


}
