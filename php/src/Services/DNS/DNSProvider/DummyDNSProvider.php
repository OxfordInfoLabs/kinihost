<?php


namespace Kinihost\Services\DNS\DNSProvider;


use Kinihost\ValueObjects\DNS\DNSRecord;

/**
 * Dummy DNS provider - useful for testing
 *
 * Class DummyDNSProvider
 * @package Kinihost\Services\DNS\DNSProvider
 */
class DummyDNSProvider implements DNSProvider {

    /**
     * Add a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function addDNSRecords($zoneIdentifier, $dnsRecords) {
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
        return [];
    }

    /**
     * Delete a DNS record
     *
     * @param string $zoneIdentifier
     * @param DNSRecord[] $dnsRecords
     */
    public function deleteDNSRecords($zoneIdentifier, $dnsRecords) {
    }
}
