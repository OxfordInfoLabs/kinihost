<?php


namespace Kinihost\Services\DNS\DNSProvider;

use Kinihost\ValueObjects\DNS\DNSRecord;
use Kinihost\TestBase;

include_once "autoloader.php";

class CloudflareDNSProviderTest extends TestBase {

    /**
     * @var CloudflareDNSProvider
     */
    private $dnsProvider;


    public function setUp(): void {
        $this->dnsProvider = new CloudflareDNSProvider();
    }


    public function testCanCreateGetAndRemoveDNSRecordsInTestZone() {



        $exampleA = date("U") . ".oxfordcybertest.site";

        // Add an A record
        $this->dnsProvider->addDNSRecords("oxfordcybertest.site", [new DNSRecord($exampleA, "A", "55.55.55.55")]);

//        $recordSets = $this->service->resourceRecordSets->listResourceRecordSets("oxfordcyber-test", "oxford-cyber-test")->getRrsets();
//
//        $indexed = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $recordSets);
//
//        $match = $indexed[$exampleA . "."];
//
//        $this->assertEquals($exampleA . ".", $match->getName());
//        $this->assertEquals("1.1.1.1", $match->getRrDatas()[0]);


        // Get A records for a label
        $existingRecords = $this->dnsProvider->getDNSRecords("oxfordcybertest.site", $exampleA);
        $this->assertEquals(1, sizeof($existingRecords));
        $this->assertEquals(new DNSRecord($exampleA, "A", ["55.55.55.55"],1), $existingRecords[0]);


        // Now delete and check it went.
        $this->dnsProvider->deleteDNSRecords("oxfordcybertest.site", [new DNSRecord($exampleA, "A", "55.55.55.55")]);


        $existingRecords = $this->dnsProvider->getDNSRecords("oxfordcybertest.site", $exampleA);
        $this->assertEquals(0, sizeof($existingRecords));



    }

}
