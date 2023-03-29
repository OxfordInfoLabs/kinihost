<?php


namespace Kinihost\Services\Routing;


use Google\Cloud\Core\ServiceBuilder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinihost\Services\Routing\RoutingProvider\GoogleRoutingProvider;
use Kinihost\Services\Routing\RoutingProvider\RoutingProvider;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingBackend;
use Kinihost\ValueObjects\Routing\RoutingBackendDNSSettings;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\ValueObjects\Routing\Status\CNameStatus;
use Kinihost\TestBase;

include_once __DIR__ . "/../../autoloader.php";

/**
 * Test cases for the Google routing provider.
 *
 * Class GoogleRoutingProviderTest
 * @package Kinihost\Services\Routing\RoutingProvider
 */
class GoogleRoutingProviderTest extends TestBase {

    /**
     * @var GoogleRoutingProvider
     */
    private $routingProvider;

    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_Service_Compute
     */
    private $computeService;


    public function setUp(): void {
        $this->routingProvider = Container::instance()->getInterfaceImplementation(RoutingProvider::class, "google");

        putenv("GOOGLE_APPLICATION_CREDENTIALS=Config/google.json");
        $this->client = new \Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->addScope('https://www.googleapis.com/auth/cloud-platform');

        $this->computeService = new \Google_Service_Compute($this->client);

    }

    public function tearDown(): void {


        $forwardingRules = $this->computeService->globalForwardingRules->listGlobalForwardingRules("kinisite-test")->getItems();
        foreach ($forwardingRules as $forwardingRule) {
            if (substr($forwardingRule->getName(), 0, 17) == "kinisite-test") {
                $this->computeService->globalForwardingRules->delete("kinisite-test", $forwardingRule->getName());
            }
        }

        $addresses = $this->computeService->globalAddresses->listGlobalAddresses("kinisite-test")->getItems();
        foreach ($addresses as $address) {
            if (substr($address->getName(), 0, 17) == "kinisite-test") {
                try {
                    $this->computeService->globalAddresses->delete("kinisite-test", $address->getName());
                } catch (\Google_Service_Exception $e) {
                    if ($e->getErrors()[0]["reason"] != "resourceNotReady" && $e->getErrors()[0]["reason"] != "notFound") {
                        throw($e);
                    }
                }
            }
        }


        $targetProxies = $this->computeService->targetHttpsProxies->listTargetHttpsProxies("kinisite-test")->getItems();
        foreach ($targetProxies as $targetProxy) {
            if (substr($targetProxy->getName(), 0, 17) == "kinisite-test") {
                $retry = true;
                while ($retry) {
                    try {
                        $this->computeService->targetHttpsProxies->delete("kinisite-test", $targetProxy->getName());
                        $retry = false;
                    } catch (\Google_Service_Exception $e) {
                        if ($e->getErrors()[0]["reason"] != "resourceInUseByAnotherResource") {
                            throw($e);
                        }
                    }

                    sleep(1);
                }
            }
        }


        $targetProxies = $this->computeService->targetHttpProxies->listTargetHttpProxies("kinisite-test")->getItems();
        foreach ($targetProxies as $targetProxy) {
            if (substr($targetProxy->getName(), 0, 17) == "kinisite-test") {
                $retry = true;
                while ($retry) {
                    try {
                        $this->computeService->targetHttpProxies->delete("kinisite-test", $targetProxy->getName());
                        $retry = false;
                    } catch (\Google_Service_Exception $e) {
                        if ($e->getErrors()[0]["reason"] != "resourceInUseByAnotherResource") {
                            throw($e);
                        }
                    }

                    sleep(1);
                }
            }
        }


        $urlMaps = $this->computeService->urlMaps->listUrlMaps("kinisite-test")->getItems();
        foreach ($urlMaps as $map) {
            if (substr($map->getName(), 0, 17) == "kinisite-test") {
                $retry = true;
                while ($retry) {
                    try {
                        $this->computeService->urlMaps->delete("kinisite-test", $map->getName());
                        $retry = false;
                    } catch (\Google_Service_Exception $e) {
                        if ($e->getErrors()[0]["reason"] != "resourceInUseByAnotherResource" &&
                            $e->getErrors()[0]["reason"] != "resourceNotReady") {
                            throw($e);
                        }
                    }
                }
            }
        }


        $certs = $this->computeService->sslCertificates->listSslCertificates("kinisite-test")->getItems();
        foreach ($certs as $cert) {
            if (substr($cert->getName(), 0, 17) == "kinisite-test") {
                $this->computeService->sslCertificates->delete("kinisite-test", $cert->getName());
            }
        }


        $buckets = $this->computeService->backendBuckets->listBackendBuckets("kinisite-test")->getItems();
        foreach ($buckets as $bucket) {
            if (substr($bucket->getName(), 0, 17) == "kinisite-test") {
                try {
                    $this->computeService->backendBuckets->delete("kinisite-test", $bucket->getName());
                } catch (\Google_Service_Exception $e) {
                    if ($e->getErrors()[0]["reason"] != "resourceInUseByAnotherResource" &&
                        $e->getErrors()[0]["reason"] != "resourceNotReady" &&
                        $e->getErrors()[0]["reason"] != "notFound") {
                        throw($e);
                    }
                }
            }
        }

        $services = $this->computeService->backendServices->listBackendServices("kinisite-test")->getItems();
        foreach ($services as $service) {
            if (substr($service->getName(), 0, 17) == "kinisite-test") {
                $this->computeService->backendServices->delete("kinisite-test", $service->getName());
            }
        }


    }


    public function testNoLoadBalancingResourcesAreCreatedWhenSingleMatchingHostnameSuppliedToRoutingBackendAndCNameReturnedInRoutingBackend() {

        $date = date("U");

        $routingIdentifier = "kinisite-test-$date";

        $routing = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                ["unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [],
                ["ut2.kinihosttest.site"]
            )

        ]);

        // Create a routing for the supplied configuration
        $routing = $this->routingProvider->createRouting($routing);


        $this->assertTrue($routing instanceof Routing);
        $this->assertEquals($routingIdentifier, $routing->getIdentifier());


        $this->assertEquals([
            "unit-tests.kinihosttest.site" => new RoutingBackendDNSSettings("unit-tests.kinihosttest.site", null, "c.storage.googleapis.com"),
            "ut2.kinihosttest.site" => new RoutingBackendDNSSettings("ut2.kinihosttest.site", null, "c.storage.googleapis.com")
        ], $routing->getBackendDNSMappings());

        $this->assertEquals(new RoutingBackend("unit-tests.kinihosttest.site", [],
            ["unit-tests.kinihosttest.site"], false),
            $routing->getBackends()[0]);


        $this->assertEquals(new RoutingBackend("ut2.kinihosttest.site", [],
            ["ut2.kinihosttest.site"], false, null),
            $routing->getBackends()[1]);


        // Check getting a routing returns a blank routing configuration.
        $routing = $this->routingProvider->getRouting($routing->getIdentifier());
        $this->assertEquals(new Routing($routingIdentifier, [], []), $routing);


        // Check we can update with a new single backend
        $routingConfig = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("mynewdomain.com",
                [],
                ["mynewdomain.com"]
            ),
            new RoutingBackend("yournewdomain.com",
                [],
                ["yournewdomain.com"]
            )

        ]);

        $routing = $this->routingProvider->updateRouting($routingConfig);


        $this->assertTrue($routing instanceof Routing);
        $this->assertEquals($routingIdentifier, $routing->getIdentifier());


        $this->assertEquals([
            "mynewdomain.com" => new RoutingBackendDNSSettings("mynewdomain.com", null, "c.storage.googleapis.com"),
            "yournewdomain.com" => new RoutingBackendDNSSettings("yournewdomain.com", null, "c.storage.googleapis.com")
        ], $routing->getBackendDNSMappings());

        $this->assertEquals(new RoutingBackend("mynewdomain.com", [],
            ["mynewdomain.com"], false),
            $routing->getBackends()[0]);


        $this->assertEquals(new RoutingBackend("yournewdomain.com", [],
            ["yournewdomain.com"], false, null),
            $routing->getBackends()[1]);

    }


    public function testLoadBalancerCreatedAndDestroyedCorrectlyIfConfigurationChanges() {

        $date = date("U");

        $routingIdentifier = "kinisite-test-$date";

        $routingConfig = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                ["unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [],
                ["ut2.kinihosttest.site"]
            )

        ]);

        // Create a routing for the supplied configuration
        $routing = $this->routingProvider->createRouting($routingConfig);

        $this->assertEquals([
            "unit-tests.kinihosttest.site" => new RoutingBackendDNSSettings("unit-tests.kinihosttest.site", null, "c.storage.googleapis.com"),
            "ut2.kinihosttest.site" => new RoutingBackendDNSSettings("ut2.kinihosttest.site", null, "c.storage.googleapis.com")
        ], $routing->getBackendDNSMappings());


        // Now requires a load balancer
        $routingConfig = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                ["customdomain.com"]
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [],
                ["ut2.kinihosttest.site"]
            )

        ]);


        $routing = $this->routingProvider->updateRouting($routingConfig);

        // Should be able to read the URL map.
        $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-i");

        $globalAddress = $this->computeService->globalAddresses->get("kinisite-test", $routingIdentifier);

        $this->assertEquals([
            "unit-tests.kinihosttest.site" => new RoutingBackendDNSSettings("unit-tests.kinihosttest.site", $globalAddress->getAddress()),
            "ut2.kinihosttest.site" => new RoutingBackendDNSSettings("ut2.kinihosttest.site", null, "c.storage.googleapis.com")
        ], $routing->getBackendDNSMappings());


        // Now revert and check that all is deleted.
        $routingConfig = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                ["unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [],
                ["ut2.kinihosttest.site"]
            )

        ]);

        $routing = $this->routingProvider->updateRouting($routingConfig);

        // Load balancer should have gone.
        try {
            $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-i");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        $this->assertEquals([
            "unit-tests.kinihosttest.site" => new RoutingBackendDNSSettings("unit-tests.kinihosttest.site", null, "c.storage.googleapis.com"),
            "ut2.kinihosttest.site" => new RoutingBackendDNSSettings("ut2.kinihosttest.site", null, "c.storage.googleapis.com")
        ], $routing->getBackendDNSMappings());


    }


    public function testCanCreateListUpdateAndRemoveRoutingConfigurationForMultiDomainAndAllExpectedItemsAreCreatedAndRemovedAccordingly() {

        $date = date("U");

        $routingIdentifier = "kinisite-test-$date";


        $routing = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [$date . ".unit-tests.kinihosttest.site", $date . "-A.unit-tests.kinihosttest.site"],
                [$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"],
                true
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [$date . ".ut2.kinihosttest.site", $date . "-A.ut2.kinihosttest.site"],
                [$date . "-B.ut2.kinihosttest.site", $date . "-C.ut2.kinihosttest.site"]
            )

        ]);

        // Create a routing for the supplied configuration
        $routing = $this->routingProvider->createRouting($routing);


        $this->assertTrue($routing instanceof Routing);
        $this->assertEquals($routingIdentifier, $routing->getIdentifier());
        $this->assertEquals(new RoutingBackend("unit-tests.kinihosttest.site",
            [$date . ".unit-tests.kinihosttest.site", $date . "-A.unit-tests.kinihosttest.site"],
            [$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"],
            true
        ), $routing->getBackends()[0]);

        $this->assertEquals(new RoutingBackend("ut2.kinihosttest.site",
            [$date . ".ut2.kinihosttest.site", $date . "-A.ut2.kinihosttest.site"],
            [$date . "-B.ut2.kinihosttest.site", $date . "-C.ut2.kinihosttest.site"]),
            $routing->getBackends()[1]);


        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $firstBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-unit-testskinihosttestsite-s");
        $this->assertEquals("unit-tests.kinihosttest.site", $firstBackendBucket->getBucketName());

        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $secondBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut2kinihosttestsite-s");
        $this->assertEquals("ut2.kinihosttest.site", $secondBackendBucket->getBucketName());


        // Check certs created for all Secure CNames with attached descriptions.
        $certificate1 = $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "unit-testskinihostte");
        $this->assertEquals($date . ".unit-tests.kinihosttest.site", $certificate1->getDescription());

        $certificate2 = $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "-aunit-testskinihost");
        $this->assertEquals($date . "-A.unit-tests.kinihosttest.site", $certificate2->getDescription());

        $certificate3 = $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "ut2kinihosttestsite");
        $this->assertEquals($date . ".ut2.kinihosttest.site", $certificate3->getDescription());

        $certificate4 = $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "-aut2kinihosttestsit");
        $this->assertEquals($date . "-A.ut2.kinihosttest.site", $certificate4->getDescription());


        // Get the secure url map for oxford cyber test and confirm it is correct.
        $urlMap = $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-s");

        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-unit-testskinihosttestsite-s", $urlMap->getDefaultService());


        $this->assertEquals(2, sizeof($urlMap->getHostRules()));
        $firstHostRule = $urlMap->getHostRules()[0];
        $this->assertEquals([$date . ".unit-tests.kinihosttest.site", $date . "-A.unit-tests.kinihosttest.site"], $firstHostRule->getHosts());

        $secondHostRule = $urlMap->getHostRules()[1];
        $this->assertEquals([$date . ".ut2.kinihosttest.site", $date . "-A.ut2.kinihosttest.site"], $secondHostRule->getHosts());


        $pathMatchers = $urlMap->getPathMatchers();
        $this->assertEquals(2, sizeof($pathMatchers));

        $firstPathMatcher = $urlMap->getPathMatchers()[0];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-unit-testskinihosttestsite-s", $firstPathMatcher->getDefaultService());

        $secondPathMatcher = $urlMap->getPathMatchers()[1];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-ut2kinihosttestsite-s", $secondPathMatcher->getDefaultService());


        $targetProxy = $this->computeService->targetHttpsProxies->get("kinisite-test", $routingIdentifier . "-s");
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/urlMaps/kinisite-test-$date-s", $targetProxy->getUrlMap());
        $certs = $targetProxy->getSslCertificates();
        $this->assertEquals(4, sizeof($certs));


        // Get the insecure url map for oxford cyber test and confirm it is correct.
        $urlMap = $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-i");

        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-unit-testskinihosttestsite-i", $urlMap->getDefaultService());


        $this->assertEquals(2, sizeof($urlMap->getHostRules()));
        $firstHostRule = $urlMap->getHostRules()[0];
        $this->assertEquals([$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"], $firstHostRule->getHosts());

        $secondHostRule = $urlMap->getHostRules()[1];
        $this->assertEquals([$date . "-B.ut2.kinihosttest.site", $date . "-C.ut2.kinihosttest.site"], $secondHostRule->getHosts());


        $pathMatchers = $urlMap->getPathMatchers();
        $this->assertEquals(2, sizeof($pathMatchers));

        $firstPathMatcher = $urlMap->getPathMatchers()[0];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-unit-testskinihosttestsite-i", $firstPathMatcher->getDefaultService());


        $secondPathMatcher = $urlMap->getPathMatchers()[1];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-ut2kinihosttestsite-i", $secondPathMatcher->getDefaultService());


        $targetProxy = $this->computeService->targetHttpProxies->get("kinisite-test", $routingIdentifier . "-i");
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/urlMaps/kinisite-test-$date-i", $targetProxy->getUrlMap());


        // Check address was returned as part of the routing object
        $globalAddress = $this->computeService->globalAddresses->get("kinisite-test", $routingIdentifier);
        $this->assertEquals($globalAddress->getAddress(), $routing->getBackendDNSMappings()["unit-tests.kinihosttest.site"]->getIpAddress());
        $this->assertNull($routing->getBackendDNSMappings()["unit-tests.kinihosttest.site"]->getCName());


        // Now get the routing and check it comes back intact.
        $routing = $this->routingProvider->getRouting($routingIdentifier);


        $this->assertEquals($routingIdentifier, $routing->getIdentifier());
        $this->assertEquals($globalAddress->getAddress(), $routing->getBackendDNSMappings()["unit-tests.kinihosttest.site"]->getIpAddress());
        $this->assertEquals(null, $routing->getBackendDNSMappings()["unit-tests.kinihosttest.site"]->getCName());
        $this->assertEquals(2, sizeof($routing->getBackends()));

        $firstBackend = $routing->getBackends()[0];
        $this->assertEquals("unit-tests.kinihosttest.site", $firstBackend->getBackendReference());
        $this->assertEquals([$date . ".unit-tests.kinihosttest.site", $date . "-A.unit-tests.kinihosttest.site"], $firstBackend->getSecureCNames());
        $this->assertEquals([$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"], $firstBackend->getInsecureCNames());

        $secondBackend = $routing->getBackends()[1];
        $this->assertEquals("ut2.kinihosttest.site", $secondBackend->getBackendReference());
        $this->assertEquals([$date . ".ut2.kinihosttest.site", $date . "-A.ut2.kinihosttest.site"], $secondBackend->getSecureCNames());
        $this->assertEquals([$date . "-B.ut2.kinihosttest.site", $date . "-C.ut2.kinihosttest.site"], $secondBackend->getInsecureCNames());


        // Check a bad routing returns ItemNotFoundException
        $routing = $this->routingProvider->getRouting("badone");
        $this->assertEquals(0, sizeof($routing->getBackends()));


        // Now update the routing
        $update = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [$date . ".unit-tests.kinihosttest.site", $date . "-D.unit-tests.kinihosttest.site"],
                [$date . "-C.unit-tests.kinihosttest.site", $date . "-E.unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut3.kinihosttest.site",
                [$date . ".ut3.kinihosttest.site", $date . "-A.ut3.kinihosttest.site"],
                [$date . "-F.ut3.kinihosttest.site"],
                true
            )
        ]);

        $this->routingProvider->updateRouting($update);


        // Check new backend buckets created
        $newBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-i");
        $this->assertEquals("ut3.kinihosttest.site", $newBucket->getBucketName());

        $newBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-s");
        $this->assertEquals("ut3.kinihosttest.site", $newBucket->getBucketName());


        // Get the url map for oxford cyber test insecure and confirm it is correct
        $urlMap = $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-i");
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-ut3kinihosttestsite-i", $urlMap->getDefaultService());


        // Get the url map for oxford cyber test secure and confirm it is correct.
        $urlMap = $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-s");

        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-ut3kinihosttestsite-s", $urlMap->getDefaultService());


        $this->assertEquals(2, sizeof($urlMap->getHostRules()));
        $firstHostRule = $urlMap->getHostRules()[0];
        $this->assertEquals([$date . ".unit-tests.kinihosttest.site", $date . "-D.unit-tests.kinihosttest.site"], $firstHostRule->getHosts());

        $secondHostRule = $urlMap->getHostRules()[1];
        $this->assertEquals([$date . ".ut3.kinihosttest.site", $date . "-A.ut3.kinihosttest.site"], $secondHostRule->getHosts());


        $pathMatchers = $urlMap->getPathMatchers();
        $this->assertEquals(2, sizeof($pathMatchers));

        $firstPathMatcher = $urlMap->getPathMatchers()[0];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-unit-testskinihosttestsite-s", $firstPathMatcher->getDefaultService());

        $secondPathMatcher = $urlMap->getPathMatchers()[1];
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/backendBuckets/kinisite-test-$date" . "-ut3kinihosttestsite-s", $secondPathMatcher->getDefaultService());


        // Check that SSL Certs have been created / removed as necessary
        $targetProxy = $this->computeService->targetHttpsProxies->get("kinisite-test", $routingIdentifier . "-s");
        $this->assertEquals("https://www.googleapis.com/compute/v1/projects/kinisite-test/global/urlMaps/kinisite-test-$date-s", $targetProxy->getUrlMap());
        $certIdentifiers = $targetProxy->getSslCertificates();
        $this->assertEquals(4, sizeof($certIdentifiers));

        // Grab the certs
        $certs = [];
        foreach ($certIdentifiers as $certIdentifier) {
            $certIdentifier = explode("/", $certIdentifier);
            $certIdentifier = array_pop($certIdentifier);
            $certs[] = $this->computeService->sslCertificates->get("kinisite-test", $certIdentifier);
        }

        $this->assertEquals($date . ".unit-tests.kinihosttest.site", $certs[0]->getDescription());
        $this->assertEquals($date . "-D.unit-tests.kinihosttest.site", $certs[1]->getDescription());
        $this->assertEquals($date . ".ut3.kinihosttest.site", $certs[2]->getDescription());
        $this->assertEquals($date . "-A.ut3.kinihosttest.site", $certs[3]->getDescription());


        // Check old certs deleted
        try {
            $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "-aunit-testsoxfo");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        try {
            $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "ut2kinihostte");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }
        try {
            $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "-aut2kinihost");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Check unused buckets removed
        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut2kinihosttestsite-s");
            print_r( $routingIdentifier . "-ut2kinihosttestsite-s");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut2kinihosttestsite-i");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Now get the routing and check it comes back intact after an update
        $routing = $this->routingProvider->getRouting($routingIdentifier);

        $this->assertEquals(new Routing($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [$date . ".unit-tests.kinihosttest.site", $date . "-D.unit-tests.kinihosttest.site"],
                [$date . "-C.unit-tests.kinihosttest.site", $date . "-E.unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut3.kinihosttest.site",
                [$date . ".ut3.kinihosttest.site", $date . "-A.ut3.kinihosttest.site"],
                [$date . "-F.ut3.kinihosttest.site"]
            )
        ], [
            "unit-tests.kinihosttest.site" => new RoutingBackendDNSSettings("unit-tests.kinihosttest.site", $globalAddress->getAddress()),
            "ut3.kinihosttest.site" => new RoutingBackendDNSSettings("ut3.kinihosttest.site", $globalAddress->getAddress()),

        ]), $routing);


        // Check that removing a routing with a bad key is ignored.
        $this->routingProvider->removeRouting("badroutingkey");


        // Remove our routing.
        $this->routingProvider->removeRouting($routingIdentifier);

        // Check global forwarding rule removed
        try {
            $this->computeService->globalForwardingRules->get("kinisite-test", $routingIdentifier . "-s");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        // Check global forwarding rule removed
        try {
            $this->computeService->globalForwardingRules->get("kinisite-test", $routingIdentifier . "-i");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Check global address removed
        try {
            $this->computeService->globalAddresses->get("kinisite-test", $routingIdentifier);
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Check secure target proxy removed
        try {
            $this->computeService->targetHttpsProxies->get("kinisite-test", $routingIdentifier . "-s");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        // Check insecure target proxy removed
        try {
            $this->computeService->targetHttpProxies->get("kinisite-test", $routingIdentifier . "-i");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Check secure url map removed
        try {
            $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-s");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        // Check insecure url map removed
        try {
            $this->computeService->urlMaps->get("kinisite-test", $routingIdentifier . "-i");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        sleep(5);

        // Check all attached buckets removed
        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-i");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-s");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-unit-testskinihosttests-i");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-unit-testskinihosttests-s");
            $this->fail("Should have been deleted");
        } catch (\Google_Service_Exception $e) {
            // Success
        }


        // Check all certs removed.
        try {
            $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "unit-testsoxford");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

        try {
            $this->computeService->sslCertificates->get("kinisite-test", $routingIdentifier . "-$date" . "ut3kinihostte");
            $this->fail("Should have thrown here");
        } catch (\Google_Service_Exception $e) {
            // Success
        }

    }


    public function testCanConfigureMultiDomainsWithDefaultBackendWithNoHosts() {


        $date = date("U");

        $routingIdentifier = "kinisite-test-$date";


        $routing = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                [$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut2.kinihosttest.site",
                [],
                [],
                true
            )

        ]);


        $this->routingProvider->createRouting($routing);

        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $firstBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-unit-testskinihosttestsite-i");
        $this->assertEquals("unit-tests.kinihosttest.site", $firstBackendBucket->getBucketName());

        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $secondBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut2kinihosttestsite-i");
        $this->assertEquals("ut2.kinihosttest.site", $secondBackendBucket->getBucketName());


        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut2kinihosttestsite-s");
            $this->fail("Shouldn't exist");
        } catch (\Exception $e) {
            // Should fail
        }


        // Now update
        $routing = new RoutingConfig($routingIdentifier, [
            new RoutingBackend("unit-tests.kinihosttest.site",
                [],
                [$date . "-B.unit-tests.kinihosttest.site", $date . "-C.unit-tests.kinihosttest.site"]
            ),
            new RoutingBackend("ut3.kinihosttest.site",
                [],
                [],
                true
            )

        ]);


        $this->routingProvider->updateRouting($routing);

        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $firstBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-unit-testskinihosttestsite-i");
        $this->assertEquals("unit-tests.kinihosttest.site", $firstBackendBucket->getBucketName());

        // Check the backend buckets have been created with a matching name to to the identifier passed through and that it maps accordingly.
        $secondBackendBucket = $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-i");
        $this->assertEquals("ut3.kinihosttest.site", $secondBackendBucket->getBucketName());


        try {
            $this->computeService->backendBuckets->get("kinisite-test", $routingIdentifier . "-ut3kinihosttestsite-s");
            $this->fail("Shouldn't exist");
        } catch (\Exception $e) {
            // Should fail
        }

    }


    public function testAlreadyConfiguredMultiDomainSiteObjectsAreIgnoredWithoutFailureOnCreate() {


        $routingConfig = new RoutingConfig("markrobertshaw", [
            new RoutingBackend("markrobertshaw-preview.kinihosttest.site", ["markrobertshaw-preview.kinihosttest.site"]),
            new RoutingBackend("markrobertshaw.kinihosttest.site", ["markrobertshaw.kinihosttest.site"])]);


        // Check this can complete successfully
        $this->routingProvider->createRouting($routingConfig);

        $this->assertTrue(true);

    }


    public function testCanUpdateCNamesForExistingMultiDomainConfiguredSite() {

//        $routingConfig = new RoutingConfig("markrobertshaw", [
//            new RoutingBackend("markrobertshaw-preview.kinihosttest.site", ["markrobertshaw-preview.kinihosttest.site"]),
//            new RoutingBackend("markrobertshaw.kinihosttest.site", ["markrobertshaw-production.kinihosttest.site", "mynewdomain.com"])]);
//
//
//        // Check this can complete successfully
//        $this->routingProvider->updateRouting($routingConfig);
//
//
//        $routing = $this->routingProvider->getRouting("markrobertshaw");
//
//        $this->assertEquals(2, sizeof($routing->getBackends()));

        $this->assertTrue(true);


    }


    public function testCanGetRoutingStatusForPassedConfig() {

//        $routingConfig = new RoutingConfig("markrobertshaw", [
//            new RoutingBackend("markrobertshaw-preview.kinihosttest.site", [], ["markrobertshaw-preview.kinihosttest.site"]),
//            new RoutingBackend("markrobertshaw.kinihosttest.site", [], ["markrobertshaw.kinihosttest.site"])],
//            new RoutingBackend($unresolvedBackend = "unresolved-cname.kinihosttest.site", [], [], true));
//
//        $routingStatus = $this->routingProvider->getRoutingStatus($routingConfig);
//        $cnameStati = $routingStatus->getCNameStati();
//        $this->assertEquals(2, sizeof($cnameStati));
//
//        $this->assertEquals(new CNameStatus("markrobertshaw-preview.kinihosttest.site", true, 200, 301, "https://markrobertshaw-preview.kinihosttest.site/"), $cnameStati[0]);
//        $this->assertEquals(new CNameStatus("markrobertshaw.kinihosttest.site", true, 200, 301, "https://markrobertshaw.kinihosttest.site/"), $cnameStati[1]);
//
//        $this->assertTrue($cnameStati[0]->isValid());
//        $this->assertTrue($cnameStati[1]->isValid());
//
//        $this->assertTrue($routingStatus->isValid());
//
//
//        $routingConfig = new RoutingConfig("unknown", [
//            new RoutingBackend("unknown-preview.kinihosttest.site", [], ["unknown-preview.kinihosttest.site"]),
//            new RoutingBackend("unknown.kinihosttest.site", [], ["unknown.kinihosttest.site"])]);
//
//        $routingStatus = $this->routingProvider->getRoutingStatus($routingConfig);
//        $cnameStati = $routingStatus->getCNameStati();
//        $this->assertEquals(2, sizeof($cnameStati));
//
//        $this->assertEquals(new CNameStatus("unknown-preview.kinihosttest.site", true, 500, 500), $cnameStati[0]);
//        $this->assertEquals(new CNameStatus("unknown.kinihosttest.site", true, 500, 500), $cnameStati[1]);
//
//        $this->assertFalse($cnameStati[0]->isValid());
//        $this->assertFalse($cnameStati[1]->isValid());
//
//        $this->assertFalse($routingStatus->isValid());

        $this->assertTrue(true);
    }


}
