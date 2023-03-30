<?php

// Ensure autoloader run from vendor.
use Kinihost\Services\Site\SiteService;
use Kinihost\ValueObjects\Site\SiteDescriptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Init;

include_once "../vendor/autoload.php";

// Ensure basic initialisation has occurred.
Container::instance()->get(Init::class);

// Grab site name
echo "Enter site name: ";
$siteName = fgets(STDIN);

/**
 * @var SiteService $siteService
 */
$siteService = Container::instance()->get(SiteService::class);

$newSite = $siteService->createSite(new SiteDescriptor($siteName), 0);
$siteService->activateSite($newSite->getSiteId());
