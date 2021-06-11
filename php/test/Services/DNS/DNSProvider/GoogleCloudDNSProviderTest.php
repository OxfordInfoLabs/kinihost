<?php

namespace Kinihost\Services\DNS\DNSProvider;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinihost\ValueObjects\DNS\DNSRecord;
use Kinihost\TestBase;


include_once "autoloader.php";

/**
 * Test cases for the google cloud DNS provider
 *
 * Class GoogleCloudDNSProviderTest
 */
class GoogleCloudDNSProviderTest extends TestBase {

    /**
     * @var GoogleCloudDNSProvider
     */
    private $dnsProvider;


    /**
     * @var \Google_Service_Dns
     */
    private $service;

    public function setUp(): void {
        $this->dnsProvider = Container::instance()->get(GoogleCloudDNSProvider::class);

        putenv("GOOGLE_APPLICATION_CREDENTIALS=Config/google.json");
        $client = new \Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');

        $this->service = new \Google_Service_Dns($client);

    }


    public function testCanCreateGetAndRemoveDNSRecordsInTestZone() {


        $exampleA = date("U") . ".kinihosttest.site";

        // Add an A record
        $this->dnsProvider->addDNSRecords("kinihosttest.site", [new DNSRecord($exampleA, "A", "1.1.1.1")]);

        $recordSets = $this->service->resourceRecordSets->listResourceRecordSets("kinisite-test", "kinihosttest")->getRrsets();

        $indexed = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $recordSets);

        $match = $indexed[$exampleA . "."];

        $this->assertEquals($exampleA . ".", $match->getName());
        $this->assertEquals("1.1.1.1", $match->getRrDatas()[0]);


        // Get A records for a label
        $existingRecords = $this->dnsProvider->getDNSRecords("kinihosttest.site", $exampleA);
        $this->assertEquals(1, sizeof($existingRecords));
        $this->assertEquals(new DNSRecord($exampleA, "A", ["1.1.1.1"]), $existingRecords[0]);


        // Now delete and check it went.
        $this->dnsProvider->deleteDNSRecords("kinihosttest.site", [new DNSRecord($exampleA, "A", "1.1.1.1")]);


        $existingRecords = $this->dnsProvider->getDNSRecords("kinihosttest.site", $exampleA);
        $this->assertEquals(0, sizeof($existingRecords));

    }



}
