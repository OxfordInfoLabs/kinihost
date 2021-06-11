<?php

namespace Kinihost\Services\DNS\DNSProvider;

use Kinihost\ValueObjects\DNS\DNSRecord;

/**
 * Create a DNS record for the supplied domain name.
 *
 * @implementation google Kinihost\Services\DNS\DNSProvider\GoogleCloudDNSProvider
 * @implementation cloudflare Kinihost\Services\DNS\DNSProvider\CloudflareDNSProvider
 * @implementation dummy Kinihost\Services\DNS\DNSProvider\DummyDNSProvider
 *
 * Class DNSProvider
 */
interface DNSProvider {

    /**
     * Add a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function addDNSRecords($zoneIdentifier, $dnsRecords);


    /**
     * Get all DNS records for a zone, optionally restricted to a name
     *
     * @param string $zoneIdentifier
     * @param string $name
     *
     * @return DNSRecord[]
     */
    public function getDNSRecords($zoneIdentifier, $name = null, $type = null);


    /**
     * Delete a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function deleteDNSRecords($zoneIdentifier, $dnsRecords);


}


