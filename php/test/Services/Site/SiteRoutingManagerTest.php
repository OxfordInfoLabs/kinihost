<?php


namespace Kinihost\Services\Site;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinihost\Services\Routing\RoutingProvider\DummyRoutingProvider;
use Kinihost\Services\Routing\RoutingProvider\RoutingProvider;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingBackend;
use Kinihost\ValueObjects\Routing\RoutingBackendDNSSettings;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\ValueObjects\Routing\Status\CNameStatus;
use Kinihost\ValueObjects\Routing\Status\RoutingStatus;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteDomain;
use Kinihost\ValueObjects\Site\SiteRouting;
use Kinihost\TestBase;

include_once "autoloader.php";

class SiteRoutingManagerTest extends TestBase {

    /**
     * @var SiteRoutingManager
     */
    private $routingManager;

    /**
     * @var MockObject
     */
    private $routingProvider;

    /**
     * Set up the site routing
     */
    public function setUp(): void {

        // Grab the mock object provider
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->routingProvider = $mockObjectProvider->getMockInstance(RoutingProvider::class);
        Container::instance()->set(DummyRoutingProvider::class, $this->routingProvider);

        $this->routingManager = Container::instance()->get(SiteRoutingManager::class);

    }

    public function tearDown(): void {

        Container::instance()->set(DummyRoutingProvider::class, new DummyRoutingProvider());

    }


    public function testCreateRoutingCallsCreateRoutingOnProviderWithRoutingConfig() {

        $site = new Site("Hello world", "helloworld");

        $expectedRouting = new Routing("helloworld", [
            new RoutingBackend("testbackend"),
            new RoutingBackend("testbackend2")
        ], [
            "testbackend" => new RoutingBackendDNSSettings("testbackend", "3.3.3.3"),
            "testbackend2" => new RoutingBackendDNSSettings("testbackend2", "3.3.3.3")
        ]);

        $expectedRoutingConfig = new RoutingConfig("helloworld", [
            new RoutingBackend("helloworld-preview.kinihost.test", [], ["helloworld-preview.kinihost.test"]),
            new RoutingBackend("helloworld-production.kinihost.test", [], ["helloworld-production.kinihost.test"])
        ]);

        // Program a return value for expected routing.
        $this->routingProvider->returnValue("createRouting", $expectedRouting, [$expectedRoutingConfig]);

        $routing = $this->routingManager->createRouting($site);

        $this->assertEquals(new SiteRouting($expectedRouting, "dummy", "kinihost.test"), $routing);

    }





    public function testUpdateRoutingCallsUpdateRoutingOnProviderWithRoutingConfig() {

        $site = new Site("Hello world", "helloworld");

        $expectedRouting = new Routing("helloworld", [
            new RoutingBackend("testbackend"),
            new RoutingBackend("testbackend2")
        ], [
            "testbackend" => new RoutingBackendDNSSettings("testbackend", "3.3.3.3"),
            "testbackend2" => new RoutingBackendDNSSettings("testbackend2", "3.3.3.3")
        ]);

        $expectedRoutingConfig = new RoutingConfig("helloworld", [
            new RoutingBackend("helloworld-preview.kinihost.test", [], ["helloworld-preview.kinihost.test"]),
            new RoutingBackend("helloworld-production.kinihost.test", [], ["helloworld-production.kinihost.test"])
        ]);


        // Program a return value for expected routing.
        $this->routingProvider->returnValue("updateRouting", $expectedRouting, [$expectedRoutingConfig]);


        $this->routingManager->updateRouting($site);


        $this->assertTrue($this->routingProvider->methodWasCalled("updateRouting", [$expectedRoutingConfig]));

    }


    public function testCanGetRoutingStatusForSite() {

        $site = new Site("Hello world", "helloworld");
        $site->setSiteDomains([
            new SiteDomain("test-domain-1.com"),
            new SiteDomain("test-domain-2.org")
        ]);
        $site->setServiceDomain("kinihost.test");



        $expectedRoutingConfig = new RoutingConfig("helloworld", [
            new RoutingBackend("helloworld-preview.kinihost.test", [], ["helloworld-preview.kinihost.test"]),
            new RoutingBackend("helloworld-production.kinihost.test", [], ["test-domain-1.com", "test-domain-2.org"]),
            new RoutingBackend("unresolved-cname.kinihost.test", [], [], true)
        ]);

        $returnedStatus = new RoutingStatus([new CNameStatus("TESTER", true, 200, 200, "TEST")]);

        // Program a return value for expected routing.
        $this->routingProvider->returnValue("getRoutingStatus", $returnedStatus, [$expectedRoutingConfig]);

        $status = $this->routingManager->getRoutingStatus($site);

        $this->assertEquals($returnedStatus, $status);

    }


}
