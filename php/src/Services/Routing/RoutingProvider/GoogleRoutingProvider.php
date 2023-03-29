<?php

namespace Kinihost\Services\Routing\RoutingProvider;

use Google_Client;
use Google_Service_Compute;
use Google_Service_Compute_TargetHttpsProxiesSetSslCertificatesRequest;
use GuzzleHttp\Exception\ServerException;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\HTTP\HttpRemoteRequest;
use Kinikit\Core\HTTP\HttpRequestErrorException;
use Kinikit\Core\Util\ObjectArrayUtils;
use League\Flysystem\Config;
use Kinihost\Services\Routing\RoutingProvider\Google\Compute;
use Kinihost\Services\Routing\RoutingProvider\Google\GoogleRoutingAPI;
use Kinihost\Services\Routing\RoutingProvider\Google\SslCertificate;
use Kinihost\ValueObjects\Routing\Routing;
use Kinihost\ValueObjects\Routing\RoutingBackend;
use Kinihost\ValueObjects\Routing\RoutingBackendDNSSettings;
use Kinihost\ValueObjects\Routing\RoutingConfig;
use Kinihost\ValueObjects\Routing\Status\CNameStatus;
use Kinihost\ValueObjects\Routing\Status\RoutingStatus;

/**
 * Google routing provider using Google Load Balancer
 *
 * Class GoogleRoutingProvider
 * @package Kinihost\Services\Routing\RoutingProvider
 */
class GoogleRoutingProvider implements RoutingProvider {


    /**
     * @var GoogleRoutingAPI
     */
    private $routingAPI;


    /**
     * GoogleRoutingProvider constructor.
     *
     * @param GoogleRoutingAPI $routingAPI
     */
    public function __construct($routingAPI) {
        $this->routingAPI = $routingAPI;
    }

    /**
     * Create a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function createRouting($routing) {

        $routingIdentifier = $routing->getIdentifier();

        $insecureHostRules = [];
        $secureHostRules = [];

        $insecurePathMatchers = [];
        $securePathMatchers = [];

        $defaultInsecureBucket = null;
        $defaultSecureBucket = null;


        $certs = [];
        $routingBackends = $routing->getBackends();
        $loadBalancedBackends = [];
        $backendDNSSettings = [];
        foreach ($routingBackends as $index => $backend) {

            $backendReference = $backend->getBackendReference();

            if (!$backend->isDefaultBackend() && sizeof($backend->getSecureCNames()) < 2 && ($backend->getSecureCNames()[0] ?? $backendReference) == $backendReference
                && sizeof($backend->getInsecureCNames()) < 2 && ($backend->getInsecureCNames()[0] ?? $backendReference) == $backendReference) {
                $backendDNSSettings[$backendReference] = new RoutingBackendDNSSettings($backendReference, null, "c.storage.googleapis.com");
                continue;
            }

            $loadBalancedBackends[] = $backend;

            // Create bucket names.
            $backendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backendReference) . "-i";
            $secureBackendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backendReference) . "-s";

            // Handle default backend logic
            if ($backend->isDefaultBackend()) {
                $defaultInsecureBucket = $backendBucketName;
                $defaultSecureBucket = $secureBackendBucketName;

                // Ensure buckets get created.
                if ($routing->hasInsecureCNames()) {
                    $this->routingAPI->createBackendBucket($backendBucketName, $backendReference);
                }

                if ($routing->hasSecureCNames()) {
                    $this->routingAPI->createBackendBucket($secureBackendBucketName, $backendReference);
                }
            }


            if (sizeof($backend->getInsecureCNames()) > 0) {

                // Create a backend bucket (required for default service use)
                if (!$backend->isDefaultBackend())
                    $this->routingAPI->createBackendBucket($backendBucketName, $backendReference);


                $pathMatcherName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backendReference) . "-i";
                list($pathMatcher, $hostRule) = $this->routingAPI->getBackendBucketPathMatcherAndHostRule($pathMatcherName, $backendBucketName, $backend->getInsecureCNames());

                $insecurePathMatchers[] = $pathMatcher;
                $insecureHostRules[] = $hostRule;

            }


            if (sizeof($backend->getSecureCNames()) > 0) {

                // Create a backend bucket
                if (!$backend->isDefaultBackend())
                    $this->routingAPI->createBackendBucket($secureBackendBucketName, $backendReference);

                // Generate the path matcher and host rule from the backend bucket
                $pathMatcherName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backendReference) . "-s";
                list($securePathMatcher, $secureHostRule) = $this->routingAPI->getBackendBucketPathMatcherAndHostRule($pathMatcherName, $secureBackendBucketName, $backend->getSecureCNames());

                $securePathMatchers[] = $securePathMatcher;
                $secureHostRules[] = $secureHostRule;

                // Create new certs for the supplied CNames
                foreach ($backend->getSecureCNames() as $certIndex => $CName) {
                    $certIdentifier = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $CName);
                    $this->routingAPI->createCertificate($certIdentifier, $CName);
                    $certs[] = "global/sslCertificates/" . $certIdentifier;
                }

            }

        }


        if (sizeof($loadBalancedBackends) > 0) {


            // Create a global address
            $this->routingAPI->createGlobalAddress($routingIdentifier);


            // Generate the secure mappings
            if (sizeof($secureHostRules) > 0) {

                // Create the URL map
                $this->routingAPI->createUrlMap($routingIdentifier . "-s", "/global/backendBuckets/" . ($defaultSecureBucket ?? $secureBackendBucketName), $securePathMatchers, $secureHostRules);


                // Create the target proxy
                $this->routingAPI->createTargetProxy($routingIdentifier . "-s", $routingIdentifier . "-s", true, $certs);


                // Create the forwarding rule.
                $this->routingAPI->createForwardingRule($routingIdentifier . "-s", $routingIdentifier . "-s", $routingIdentifier, true);

            }


            if (sizeof($insecureHostRules) > 0) {

                // URL Map
                $this->routingAPI->createUrlMap($routingIdentifier . "-i", "/global/backendBuckets/" . ($defaultInsecureBucket ?? $backendBucketName), $insecurePathMatchers, $insecureHostRules);

                // Target Proxy
                $this->routingAPI->createTargetProxy($routingIdentifier . "-i", $routingIdentifier . "-i", false);

                // Forwarding rule
                $this->routingAPI->createForwardingRule($routingIdentifier . "-i", $routingIdentifier . "-i", $routingIdentifier, false);

            }


            // Grab the IP address
            $address = $this->routingAPI->getGlobalAddress($routingIdentifier);

            foreach ($loadBalancedBackends as $backend) {
                $backendDNSSettings[$backend->getBackendReference()] = new RoutingBackendDNSSettings($backend->getBackendReference(), $address->getAddress());
            }


        }

        // Return the routing with the IP address intact.
        return new Routing($routingIdentifier, $routingBackends, $backendDNSSettings);

    }


    /**
     * Update a routing using a passed config
     *
     * @param RoutingConfig $routing
     * @return Routing
     */
    public function updateRouting($routing) {

        // Check whether we need to create any routings.
        $routingBackends = $routing->getBackends();

        $loadBalancedBackends = [];
        $backendDNSSettings = [];
        foreach ($routingBackends as $index => $backend) {

            $backendReference = $backend->getBackendReference();

            if (!$backend->isDefaultBackend() && sizeof($backend->getSecureCNames()) < 2 && ($backend->getSecureCNames()[0] ?? $backendReference) == $backendReference
                && sizeof($backend->getInsecureCNames()) < 2 && ($backend->getInsecureCNames()[0] ?? $backendReference) == $backendReference) {
                $backendDNSSettings[$backendReference] = new RoutingBackendDNSSettings($backendReference, null, "c.storage.googleapis.com");
                continue;
            }

            $loadBalancedBackends[] = $backend;
        }


        $routingIdentifier = $routing->getIdentifier();


        // If no load balanced buckets, simply return
        if (sizeof($loadBalancedBackends) == 0) {

            // Ensure any load balancer is removed.
            $this->removeRouting($routing->getIdentifier());

            return new Routing($routingIdentifier, $routingBackends, $backendDNSSettings);
        }


        $currentRouting = $this->getRouting($routingIdentifier);

        // If no backends in the current config, create the routing instead.
        if (!$currentRouting->getBackends()) {
            return $this->createRouting($routing);
        }

        $currentBackends = ObjectArrayUtils::getMemberValueArrayForObjects("backendReference", $currentRouting->getBackends());
        $newBackends = ObjectArrayUtils::getMemberValueArrayForObjects("backendReference", $routing->getBackends());

        $newBackendBuckets = array_diff($newBackends, $currentBackends);
        $oldBackendBuckets = array_diff($currentBackends, $newBackends);

        $indexedBackends = ObjectArrayUtils::indexArrayOfObjectsByMember("backendReference", $routing->getBackends());


        // Create new backend buckets as required.
        foreach ($newBackendBuckets as $bucket) {

            $backend = $indexedBackends[$bucket];

            // Create an insecure backend bucket.
            if (sizeof($backend->getInsecureCNames()) > 0 || ($backend->isDefaultBackend() && $routing->hasInsecureCNames())) {
                $backendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-i";
                $this->routingAPI->createBackendBucket($backendBucketName, $backend->getBackendReference());
            }

            // Create a secure bucket if required
            if (sizeof($backend->getSecureCNames()) > 0 || ($backend->isDefaultBackend() && $routing->hasSecureCNames())) {
                // Create a backend bucket
                $secureBackendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-s";
                $this->routingAPI->createBackendBucket($secureBackendBucketName, $backend->getBackendReference());
            }
        }


        try {

            // Grab the target proxy and enumerate the current certificates.
            $secureTargetProxy = $this->routingAPI->getTargetProxy($routingIdentifier . "-s", true);

            $sslCerts = $secureTargetProxy->getSslCertificates();
            $currentCerts = [];
            foreach ($sslCerts as $sslCert) {
                $certIdentifier = explode("/", $sslCert);
                $certIdentifier = array_pop($certIdentifier);
                $currentCerts[$certIdentifier] = 1;
            }


        } catch (\Exception $e) {
            $currentCerts = [];
        }


        // Now loop through each backend and gather the various host rules we need.
        $insecureHostRules = [];
        $secureHostRules = [];

        $insecurePathMatchers = [];
        $securePathMatchers = [];
        $certs = [];

        $defaultInsecureBucket = null;
        $defaultSecureBucket = null;

        foreach ($routing->getBackends() as $index => $backend) {

            // Create bucket names.
            $backendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-i";
            $secureBackendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-s";

            if ($backend->isDefaultBackend()) {
                $defaultInsecureBucket = $backendBucketName;
                $defaultSecureBucket = $secureBackendBucketName;
            }

            if (sizeof($backend->getInsecureCNames()) > 0) {


                $pathMatcherName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-i";
                list($pathMatcher, $hostRule) = $this->routingAPI->getBackendBucketPathMatcherAndHostRule($pathMatcherName, $backendBucketName, $backend->getInsecureCNames());

                $insecurePathMatchers[] = $pathMatcher;
                $insecureHostRules[] = $hostRule;

            }


            if (sizeof($backend->getSecureCNames()) > 0) {


                // Generate the path matcher and host rule from the backend bucket
                $pathMatcherName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $backend->getBackendReference()) . "-s";
                list($securePathMatcher, $secureHostRule) = $this->routingAPI->getBackendBucketPathMatcherAndHostRule($pathMatcherName, $secureBackendBucketName, $backend->getSecureCNames());

                $securePathMatchers[] = $securePathMatcher;
                $secureHostRules[] = $secureHostRule;

                // Create new certs for the supplied CNames
                foreach ($backend->getSecureCNames() as $certIndex => $CName) {

                    $certIdentifier = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $CName);

                    if (!isset($currentCerts[$certIdentifier])) {
                        $this->routingAPI->createCertificate($certIdentifier, $CName);
                    } else {
                        unset($currentCerts[$certIdentifier]);
                    }

                    $certs[] = "global/sslCertificates/" . $certIdentifier;
                }

            }

        }


        if (sizeof($insecurePathMatchers) > 0) {

            // Grab the url map and update with latest gubbins.
            $insecureUrlMap = $this->routingAPI->getUrlMap($routingIdentifier . "-i");
            $insecureUrlMap->setDefaultService("/global/backendBuckets/" . ($defaultInsecureBucket ?? $backendBucketName));
            $insecureUrlMap->setHostRules($insecureHostRules);
            $insecureUrlMap->setPathMatchers($insecurePathMatchers);

            $this->routingAPI->updateUrlMap($insecureUrlMap);


        }


        // If we need to update the secure hosts as well, do this now.
        if (sizeof($securePathMatchers) > 0) {

            $secureUrlMap = $this->routingAPI->getUrlMap($routingIdentifier . "-s");

            $secureUrlMap->setDefaultService("/global/backendBuckets/" . ($defaultSecureBucket ?? $secureBackendBucketName));
            $secureUrlMap->setHostRules($secureHostRules);
            $secureUrlMap->setPathMatchers($securePathMatchers);

            $this->routingAPI->updateUrlMap($secureUrlMap);


            // Update any SSL certs
            $this->routingAPI->updateTargetProxySSLCerts($routingIdentifier . "-s", $certs);


        }


        // Now remove old certificates no longer in use.
        foreach ($currentCerts as $currentCertIdentifier => $value) {
            try {
                $this->routingAPI->deleteSSLCert($currentCertIdentifier);
            } catch (\Google_Service_Exception $e){
                // OK
            }
        }



        // Finally remove any backend buckets we no longer need.
        foreach ($oldBackendBuckets as $oldBackendBucket) {

            $backendBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $oldBackendBucket) . "-i";
            $secureBucketName = $this->getGoogleNameFromIdentifier($routingIdentifier . "-" . $oldBackendBucket) . "-s";

            $this->routingAPI->deleteBackendBucket($backendBucketName);

            try {
                $this->routingAPI->deleteBackendBucket($secureBucketName);
            } catch (\Google_Service_Exception $e) {
                // OK
            }


        }

        return $this->getRouting($routingIdentifier);

    }

    /**
     * Get a routing from the provider for the supplied identifier
     *
     * @param $identifier
     * @return Routing
     */
    public function getRouting($identifier) {

        try {

            $backends = [];

            $insecureUrlMap = $this->routingAPI->getUrlMap($identifier . "-i");


            // Grab the insecure host names
            foreach ($insecureUrlMap->getHostRules() as $index => $hostRule) {
                $pathMatcher = $insecureUrlMap->getPathMatchers()[$index];

                if (strpos($pathMatcher->getDefaultService(), "backendBuckets")) {

                    $backendBucketIdentifier = explode("/", $pathMatcher->getDefaultService());
                    $backendBucketIdentifier = array_pop($backendBucketIdentifier);

                    $backendBucket = $this->routingAPI->getBackendBucket($backendBucketIdentifier);
                    $backendBucketName = $backendBucket->getBucketName();

                    $insecureCNames = [];
                    foreach ($hostRule->getHosts() as $host) {
                        $insecureCNames[] = $host;
                    }

                    $backends[$backendBucketName] = new RoutingBackend($backendBucketName, [], $insecureCNames);

                }
            }

            // Grab the url map from which to derive the backends in use.
            try {
                $secureUrlMap = $this->routingAPI->getUrlMap($identifier . "-s");

                // Grab the target proxy and from that the set of SSL Certs
                $targetProxy = $this->routingAPI->getTargetProxy($identifier . "-s", true);
                $sslCerts = $targetProxy->getSslCertificates();
                $certDomains = [];
                foreach ($sslCerts as $sslCert) {
                    $certIdentifier = explode("/", $sslCert);
                    $certIdentifier = array_pop($certIdentifier);
                    $certDomains[$this->routingAPI->getSSLCertificate($certIdentifier)->getDescription()] = 1;
                }


                // Create backends from host rules
                foreach ($secureUrlMap->getHostRules() as $index => $hostRule) {

                    $pathMatcher = $secureUrlMap->getPathMatchers()[$index];


                    $backendBucketIdentifier = explode("/", $pathMatcher->getDefaultService());
                    $backendBucketIdentifier = array_pop($backendBucketIdentifier);

                    $backendBucket = $this->routingAPI->getBackendBucket($backendBucketIdentifier);
                    $backendBucketName = $backendBucket->getBucketName();


                    $insecureCNames = [];
                    if (isset($backends[$backendBucketName])) {
                        $insecureCNames = $backends[$backendBucketName]->getInsecureCNames();
                    }

                    $secureCNames = [];
                    foreach ($hostRule->getHosts() as $host) {
                        $secureCNames[] = $host;
                    }

                    $backends[$backendBucketName] = new RoutingBackend($backendBucketName, $secureCNames, $insecureCNames);

                }
            } catch (\Google_Service_Exception $e) {
                if ($e->getErrors()[0]["reason"] != "notFound") {
                    throw ($e);
                }
            }


            // Get the ipv4 address
            $ipv4Address = $this->routingAPI->getGlobalAddress($identifier)->getAddress();


            // Create backend DNS settings
            $backendDNSSettings = [];
            foreach ($backends as $backend) {
                $backendDNSSettings[$backend->getBackendReference()] = new RoutingBackendDNSSettings($backend->getBackendReference(), $ipv4Address);
            }

            return new Routing($identifier, array_values($backends), $backendDNSSettings);

        } catch (\Google_Service_Exception $e) {

            if ($e->getErrors()[0]["reason"] == "notFound") {
                return new Routing($identifier, [], []);
            } else {
                throw $e;
            }
        }

    }

    /**
     * Remove the routing with the supplied identifier.
     *
     * @param $identifier
     */
    public function removeRouting($identifier) {

        $existingRouting = $this->getRouting($identifier);

        // Delete from the top down

        try {

            // Forwarding rules
            $this->routingAPI->deleteForwardingRule($identifier . "-s");


            // Delete the secure target proxy
            $this->routingAPI->deleteTargetProxy($identifier . "-s", true);

            // Delete secure url map
            $this->routingAPI->deleteUrlMap($identifier . "-s");

        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "notFound")
                throw $e;
        }


        try {


            // Delete the forwarding rule.
            $this->routingAPI->deleteForwardingRule($identifier . "-i");

            // Delete the target proxy
            $this->routingAPI->deleteTargetProxy($identifier . "-i", false);

            // Delete the url map
            $this->routingAPI->deleteUrlMap($identifier . "-i");


            // Global Address
            $this->routingAPI->deleteGlobalAddress($identifier);

        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "notFound")
                throw $e;
        }


        // Certs and backend buckets
        foreach ($existingRouting->getBackends() as $backend) {

            $backendBucketName = $this->getGoogleNameFromIdentifier($identifier . "-" . $backend->getBackendReference()) . "-i";
            $secureBackendBucketName = $this->getGoogleNameFromIdentifier($identifier . "-" . $backend->getBackendReference()) . "-s";


            foreach ($backend->getSecureCNames() as $CName) {
                $certIdentifier = $this->getGoogleNameFromIdentifier($identifier . "-" . $CName);
                $this->routingAPI->deleteSSLCert($certIdentifier);
            }

            $this->routingAPI->deleteBackendBucket($backendBucketName);

            try {
                $this->routingAPI->deleteBackendBucket($secureBackendBucketName);
            } catch (\Google_Service_Exception $e) {
                if ($e->getErrors()[0]["reason"] != "notFound")
                    throw $e;
            }


        }


    }


    /**
     * Return a routing status object based upon a config object.
     * Used to monitor / verify status of routing.
     *
     * @param RoutingConfig $routingConfig
     * @return RoutingStatus
     */
    public function getRoutingStatus($routingConfig) {

        $cnameStati = [];

        foreach ($routingConfig->getBackends() as $routingBackend) {

            foreach ($routingBackend->getInsecureCNames() as $secureCName) {

                list ($secureResponseCode) = $this->getResponse("https://" . $secureCName);
                list ($insecureResponseCode, $insecureRedirectionDomain) = $this->getResponse("http://" . $secureCName);

                $cnameStati[] = new CNameStatus($secureCName, true, $secureResponseCode, $insecureResponseCode, $insecureRedirectionDomain);
            }


        }

        return new RoutingStatus($cnameStati);

    }


    private function getGoogleNameFromIdentifier($identifier) {
        return trim(substr(strtolower(str_replace(".", "", $identifier)), 0, 55), "-");
    }

    // Get the response from a URL - Temporary until more complete monitoring system implemented.
    private function getResponse($url) {

        $request = new HttpRemoteRequest($url, "GET");

        try {
            $request->dispatch();
        } catch (HttpRequestErrorException $e) {
            return [500, ""];
        }

        $headers = $request->getResponseHeaders();
        return [$headers["Response-Code"], $headers["Location"] ?? ""];

    }
}
