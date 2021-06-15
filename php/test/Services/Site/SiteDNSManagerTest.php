<?php


namespace Kinihost\Services\Site;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Services\DNS\DNSProvider\DNSProvider;
use Kinihost\Services\DNS\DNSProvider\DummyDNSProvider;
use Kinihost\ValueObjects\DNS\DNSRecord;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingBackend;
use Kinihost\ValueObjects\Routing\RoutingBackendDNSSettings;
use Kinihost\Objects\Site\Site;
use Kinihost\TestBase;

include_once "autoloader.php";

class SiteDNSManagerTest extends TestBase {


    /**
     * @var SiteDNSManager
     */
    private $dnsManager;

    /**
     * @var MockObject
     */
    private $dnsProvider;


    /**
     * Set up function
     */
    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);
        $this->dnsProvider = $mockObjectProvider->getMockInstance(DNSProvider::class);

        Container::instance()->set(DummyDNSProvider::class, $this->dnsProvider);

        $this->dnsManager = new SiteDNSManager();

    }


    public function testCanCreateServiceDNSForASiteIncludingPreviewAndProductionMappings() {

        $site = new Site("My Test Site", "testdnssite");
        $routing = new Routing("testdnssite", [
            new RoutingBackend("testbackend"),
            new RoutingBackend("testbackend2")
        ], [
            "testbackend" => new RoutingBackendDNSSettings("testbackend", "3.3.3.3"),
            "testbackend2" => new RoutingBackendDNSSettings("testbackend2", "3.3.3.3"),
        ]);

        $this->dnsProvider->returnValue("getDNSRecords", []);

        // Create the DNS
        $key = $this->dnsManager->createServiceDNSForSite($site, $routing);


        // Check that the manager was called with appropriate values
        $this->assertTrue($this->dnsProvider->methodWasCalled("addDNSRecords", ["kinihost.test", [
            new DNSRecord("testdnssite-preview.kinihost.test", "A", "3.3.3.3", 0),
            new DNSRecord("testdnssite-production.kinihost.test", "A", "3.3.3.3", 0)
        ]]));


        $this->assertEquals("dummy", $key);

    }





    public function testCanActivateMaintenanceModeForSite() {

        $site = new Site("My Test Site", "testdnssite");
        $site->setServiceDomain("mytest.org");

        $testRecords = [new DNSRecord("testdnssite-production.mytest.org", "CNAME", "myoldpony.com", 0)];
        $this->dnsProvider->returnValue("getDNSRecords", $testRecords, ["mytest.org", "testdnssite-production.mytest.org"]);

        $this->dnsManager->activateMaintenanceDNS($site);

        // Check old records were deleted
        $this->assertTrue($this->dnsProvider->methodWasCalled("deleteDNSRecords", ["mytest.org", $testRecords]));

        // Check maintenance records were added
        $this->assertTrue($this->dnsProvider->methodWasCalled("addDNSRecords", ["mytest.org", [
            new DNSRecord("testdnssite-production.mytest.org", "CNAME", "maintenance.mytest.org", 0)
        ]]));

    }


}
