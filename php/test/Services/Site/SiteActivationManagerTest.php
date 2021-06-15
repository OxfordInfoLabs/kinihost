<?php


namespace Kinihost\Services\Site;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinihost\ValueObjects\Site\SiteDescriptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;

use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\Status\CNameStatus;
use Kinihost\ValueObjects\Routing\Status\RoutingStatus;
use Kinihost\Objects\Site\Site;

use Kinihost\ValueObjects\Site\SiteActivationStatus;
use Kinihost\ValueObjects\Site\SiteCreateDescriptor;
use Kinihost\ValueObjects\Site\SiteRouting;
use Kinihost\TestBase;

include_once "autoloader.php";


class SiteActivationManagerTest extends TestBase {

    /**
     * @var MockObject
     */
    private $storageManager;


    /**
     * @var MockObject
     */
    private $routingManager;


    /**
     * @var MockObject
     */
    private $dnsManager;


    /**
     * @var MockObject
     */
    private $sourceService;


    /**
     * @var MockObject
     */
    private $siteDataUpdateManager;


    /**
     * @var MockObject
     */
    private $siteActivationManager;


    public function setUp(): void {

        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->storageManager = $mockObjectProvider->getMockInstance(SiteStorageManager::class);
        $this->routingManager = $mockObjectProvider->getMockInstance(SiteRoutingManager::class);
        $this->dnsManager = $mockObjectProvider->getMockInstance(SiteDNSManager::class);
        $this->sourceService = $mockObjectProvider->getMockInstance(SiteSourceService::class);

        $this->siteActivationManager = new SiteActivationManager($this->storageManager, $this->routingManager, $this->dnsManager, $this->sourceService);

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

    }


    public function testActivateSiteEnsuresStorageAndRoutingIsCreatedViaStorageManagerAndDefaultThemeActivated() {


        $this->storageManager->returnValue("createStorage", "test");
        $this->routingManager->returnValue("createRouting", new SiteRouting(new Routing("mylittlepony", [], "1.1.1.1"), "test-route", "oxfordcyber.test"));
        $this->dnsManager->returnValue("createServiceDNSForSite", "test-dns");

        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->createSite(new SiteDescriptor("ZZZZZZ"), 1);

        // Activate the site
        $this->siteActivationManager->activateSite($site);


        $this->assertTrue($this->storageManager->methodWasCalled("createStorage", [$site]));
        $this->assertTrue($this->routingManager->methodWasCalled("createRouting", [$site]));
        $this->assertTrue($this->dnsManager->methodWasCalled("createServiceDNSForSite", [$site, new SiteRouting(new Routing("mylittlepony", [], "1.1.1.1"), "test-route", "oxfordcyber.test")]));
        $this->assertTrue($this->sourceService->methodWasCalled("installBlankContent", [$site]));
        $this->assertTrue($this->sourceService->methodWasCalled("initialiseProductionContent", [$site]));

        $this->assertEquals("test", $site->getStorageProviderKey());
        $this->assertEquals("test-route", $site->getRoutingProviderKey());
        $this->assertEquals("oxfordcyber.test", $site->getServiceDomain());
        $this->assertEquals("test-dns", $site->getDnsProviderKey());;

        // Check we are still pending
        $this->assertEquals(Site::STATUS_PENDING, $site->getStatus());


    }


    public function testActivateSiteForNonSiteEnsuresStorageAndRoutingIsCreatedWithProductionActivation() {


        $this->storageManager->returnValue("createStorage", "test");
        $this->routingManager->returnValue("createRouting", new SiteRouting(new Routing("mylittlepony", [], "1.1.1.1"), "test-route", "oxfordcyber.test"));
        $this->dnsManager->returnValue("createServiceDNSForSite", "test-dns");

        $siteService = Container::instance()->get(SiteService::class);
        $site = $siteService->createSite(new SiteDescriptor("ZZZZZZZ", null), 1);

        // Activate the site
        $this->siteActivationManager->activateSite($site);


        $this->assertTrue($this->storageManager->methodWasCalled("createStorage", [$site]));
        $this->assertTrue($this->routingManager->methodWasCalled("createRouting", [$site]));
        $this->assertTrue($this->dnsManager->methodWasCalled("createServiceDNSForSite", [$site, new SiteRouting(new Routing("mylittlepony", [], "1.1.1.1"), "test-route", "oxfordcyber.test")]));
        $this->assertTrue($this->sourceService->methodWasCalled("installBlankContent", [$site]));
        $this->assertTrue($this->sourceService->methodWasCalled("initialiseProductionContent", [$site]));

        $this->assertEquals("test", $site->getStorageProviderKey());
        $this->assertEquals("test-route", $site->getRoutingProviderKey());
        $this->assertEquals("oxfordcyber.test", $site->getServiceDomain());
        $this->assertEquals("test-dns", $site->getDnsProviderKey());;

        // Check we are still pending
        $this->assertEquals(Site::STATUS_PENDING, $site->getStatus());


    }




    public function testGetActivationStatusReturnsASiteActivationStatusObject() {

        $site = Site::fetch(1);

        $routingStatus = new RoutingStatus([new CNameStatus("hello.test", true, 200, 200)]);

        $this->routingManager->returnValue("getRoutingStatus", $routingStatus, [$site]);

        $activationStatus = $this->siteActivationManager->getActivationStatus($site);

        $this->assertEquals(new SiteActivationStatus("samdavisdotcom", $routingStatus), $activationStatus);
        $this->assertTrue($activationStatus->isValid());

    }


}
