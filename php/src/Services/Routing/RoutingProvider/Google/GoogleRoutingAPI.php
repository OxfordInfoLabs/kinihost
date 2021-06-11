<?php


namespace Kinihost\Services\Routing\RoutingProvider\Google;


use Google_Client;
use Google_Service_Compute;
use Google_Service_Compute_TargetHttpsProxiesSetSslCertificatesRequest;
use Kinikit\Core\Configuration\Configuration;
use Kinihost\ValueObjects\Routing\RoutingBackend;

/**
 * @noProxy
 *
 * Class GoogleRoutingAPI
 * @package Kinihost\Services\Routing\RoutingProvider\Google
 */
class GoogleRoutingAPI {

    /**
     * @var Google_Service_Compute
     */
    private $computeService;

    /**
     * @var string
     */
    private $projectId;

    /**
     * @var string
     */
    private $region;

    /**
     * Construct the google routing provider with all required items
     *
     * GoogleRoutingProvider constructor.
     */
    public function __construct() {

        putenv("GOOGLE_APPLICATION_CREDENTIALS=" . Configuration::readParameter("google.keyfile.path"));
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');

        $this->computeService = new Compute($client);
        $this->projectId = Configuration::readParameter("google.project.id");
        $this->region = Configuration::readParameter("google.bucket.region");

    }


    /**
     * Get a global address
     *
     * @param $addressName
     * @return \Google_Service_Compute_Address
     */
    public function getGlobalAddress($addressName) {
        return $this->computeService->globalAddresses->get($this->projectId, $addressName);
    }


    /**
     * @param $routingIdentifier
     * @param RoutingBackend $backend
     * @param $backendBucketName
     * @return array
     */
    public function getBackendBucketPathMatcherAndHostRule($pathMatcherName, $backendBucketName, $hosts) {

        // Create new path and host rules.
        $pathMatcher = new \Google_Service_Compute_PathMatcher();
        $pathMatcher->setName($pathMatcherName);
        $pathMatcher->setDefaultService("global/backendBuckets/$backendBucketName");

        $hostRule = new \Google_Service_Compute_HostRule();
        $hostRule->setHosts($hosts);
        $hostRule->setPathMatcher($pathMatcherName);

        return array($pathMatcher, $hostRule);
    }


    /**
     * Get backend service path matcher and host rule for a backend service.
     *
     * @param string $pathMatcherName
     * @param string $backendServiceName
     * @param string[] $hosts
     */
    public function getBackendServicePathMatcherAndHostRule($pathMatcherName, $backendServiceName, $hosts) {

        // Create new path and host rules.
        $pathMatcher = new \Google_Service_Compute_PathMatcher();
        $pathMatcher->setName($pathMatcherName);
        $pathMatcher->setDefaultService("global/backendServices/$backendServiceName");

        $hostRule = new \Google_Service_Compute_HostRule();
        $hostRule->setHosts($hosts);
        $hostRule->setPathMatcher($pathMatcherName);

        return array($pathMatcher, $hostRule);

    }


    public function getUrlMap($mapName) {
        return $this->computeService->urlMaps->get($this->projectId, $mapName);
    }

    /**
     * Get a target proxy (either secure or not)
     *
     * @param $proxyName
     * @param $secure
     */
    public function getTargetProxy($proxyName, $secure) {

        if ($secure) {
            return $this->computeService->targetHttpsProxies->get($this->projectId, $proxyName);
        } else {
            return $this->computeService->targetHttpProxies->get($this->projectId, $proxyName);
        }

    }


    /**
     * Get a cert by name
     *
     * @param $certName
     * @return \Google_Service_Compute_SslCertificate
     */
    public function getSSLCertificate($certName) {
        return $this->computeService->sslCertificates->get($this->projectId, $certName);
    }


    /**
     * Get a backend bucket
     *
     * @param $backendBucketName
     * @return \Google_Service_Compute_BackendBucket
     */
    public function getBackendBucket($backendBucketName) {
        return $this->computeService->backendBuckets->get($this->projectId, $backendBucketName);
    }


    public function getBackendService($backendServiceName) {
        return $this->computeService->backendServices->get($this->projectId, $backendServiceName);
    }


    /**
     * Create a global address
     *
     * @param $addressName
     */
    public function createGlobalAddress($addressName) {

        // Create a new managed IP address
        $address = new \Google_Service_Compute_Address();
        $address->setName($addressName);

        try {
            $this->computeService->globalAddresses->insert($this->projectId, $address);
        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "alreadyExists")
                throw ($e);
        }
    }

    /**
     * Create a backend bucket.
     *
     * @param $name
     * @param $targetBucketName
     * @return mixed
     */
    public function createBackendBucket($name, $targetBucketName) {


        // Create the secure backend bucket if required
        $backendBucket = new \Google_Service_Compute_BackendBucket();
        $backendBucket->setName($name);
        $backendBucket->setBucketName($targetBucketName);

        try {
            $this->computeService->backendBuckets->insert($this->projectId, $backendBucket);
        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "alreadyExists")
                throw ($e);
        }

        return $name;
    }


    /**
     * Create a backend service
     *
     * @param $backendServiceName
     */
    public function createBackendService($backendServiceName, $targetInstanceGroup) {

        $zone = Configuration::readParameter("google.bucket.zone");

        $backend = new \Google_Service_Compute_Backend();
        $backend->setGroup("/zones/$zone/instanceGroups/$targetInstanceGroup");

        $backendService = new \Google_Service_Compute_BackendService();
        $backendService->setName($backendServiceName);
        $backendService->setBackends([$backend]);
        $backendService->setHealthChecks(["/global/healthChecks/http-healthcheck"]);

        try {
            $this->computeService->backendServices->insert($this->projectId, $backendService);
        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "alreadyExists")
                throw ($e);
        }

    }


    /**
     * @param $routingIdentifier
     * @param $CName
     * @return bool|string
     */
    public function createCertificate($certificateName, $CName) {

        $cert = new SslCertificate();
        $cert->setName($certificateName);
        $cert->setType("MANAGED");
        $cert->setManaged(["domains" => [$CName]]);
        $cert->setDescription($CName);

        try {
            $this->computeService->sslCertificates->insert($this->projectId, $cert);
        } catch (\Google_Service_Exception $e) {
            if ($e->getErrors()[0]["reason"] != "alreadyExists")
                throw ($e);
        }

        return $certificateName;
    }


    /**
     * @param $mapName
     * @param $backendServiceName
     * @param $pathMatchers
     * @param $hostRules
     * @return array
     * @throws \Google_Service_Exception
     */
    public function createUrlMap($mapName, $backendServiceName, $pathMatchers, $hostRules) {

        // Create the url map using the path matchers and host rules
        $urlMap = new \Google_Service_Compute_UrlMap();
        $urlMap->setName($mapName);
        if ($backendServiceName) {
            $urlMap->setDefaultService($backendServiceName);
        } else {
            $redirectAction = new \Google_Service_Compute_HttpRedirectAction();
            $redirectAction->setHttpsRedirect(true);
            $redirectAction->setPathRedirect("/");
            $urlMap->setDefaultUrlRedirect($redirectAction);
        }
        $urlMap->setPathMatchers($pathMatchers);
        $urlMap->setHostRules($hostRules);

        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->urlMaps->insert($this->projectId, $urlMap);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;

                if ($reason == "alreadyExists")
                    $notReady = false;
                else if ($reason != "resourceNotReady") {
                    throw ($e);
                } else
                    sleep(1);

            }
        }

        return $mapName;

    }

    /**
     * @param $routingIdentifier
     * @param $certs
     * @return array
     * @throws \Google_Service_Exception
     */
    public function createTargetProxy($proxyName, $urlMapName, $secure, $certs = null) {

        // Create a target proxy
        $endpoint = null;
        if ($secure) {
            $targetProxy = new \Google_Service_Compute_TargetHttpsProxy();
            $targetProxy->setSslCertificates($certs);
            $endpoint = $this->computeService->targetHttpsProxies;
        } else {
            $targetProxy = new \Google_Service_Compute_TargetHttpProxy();
            $endpoint = $this->computeService->targetHttpProxies;
        }
        $targetProxy->setName($proxyName);
        $targetProxy->setUrlMap("global/urlMaps/" . $urlMapName);


        $notReady = true;
        while ($notReady) {
            try {
                $endpoint->insert($this->projectId, $targetProxy);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason == "alreadyExists")
                    $notReady = false;

                else if ($reason != "resourceNotReady") {
                    throw ($e);
                } else
                    sleep(1);

            }
        }
        return $proxyName;
    }


    /**
     * Create the forwarding rule
     *
     * @param $routingIdentifier
     * @throws \Google_Service_Exception
     */
    public function createForwardingRule($forwardingName, $targetProxyName, $globalAddressName, $secure) {

        // Finally add the forwarding rule to route this request.
        $secureForwardingRule = new \Google_Service_Compute_ForwardingRule();
        $secureForwardingRule->setName($forwardingName);
        $secureForwardingRule->setLoadBalancingScheme("EXTERNAL");
        $secureForwardingRule->setTarget("global/targetHttp" . ($secure ? "s" : "") . "Proxies/" . $targetProxyName);
        $secureForwardingRule->setIPAddress("global/addresses/" . $globalAddressName);
        $secureForwardingRule->setPortRange($secure ? 443 : 80);


        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->globalForwardingRules->insert($this->projectId, $secureForwardingRule);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason == "alreadyExists")
                    $notReady = false;
                else if ($reason != "invalid" && $reason != "resourceNotReady") {
                    throw ($e);
                } else
                    sleep(1);

            }
        }

        return $forwardingName;
    }


    /**
     * Update a url map
     *
     * @param $urlMap
     * @throws \Google_Service_Exception
     */
    public function updateUrlMap($urlMap) {

        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->urlMaps->update($this->projectId, $urlMap->getName(), $urlMap);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {

                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason != "resourceNotReady") {
                    throw ($e);
                }
                sleep(1);

            }
        }

    }


    public function updateTargetProxySSLCerts($proxyName, $certs) {

        // Create cert update request.
        $certUpdateRequest = new Google_Service_Compute_TargetHttpsProxiesSetSslCertificatesRequest();
        $certUpdateRequest->setSslCertificates($certs);


        // Update the target proxy
        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->targetHttpsProxies->setSslCertificates($this->projectId, $proxyName, $certUpdateRequest);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason != "resourceNotReady") {
                    throw ($e);
                }
                sleep(1);

            }
        }


    }


    /**
     * Delete an SSL cert
     *
     * @param $certName
     */
    public function deleteSSLCert($certName) {
        $this->computeService->sslCertificates->delete($this->projectId, $certName);
    }


    /**
     * Delete a backend bucket.
     *
     * @param $backendBucketName
     * @throws \Google_Service_Exception
     */
    public function deleteBackendBucket($backendBucketName) {

        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->backendBuckets->delete($this->projectId, $backendBucketName);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason != "resourceInUseByAnotherResource") {
                    throw ($e);
                }
                sleep(1);

            }
        }

    }


    /**
     * Delete a backend service by name
     *
     * @param $backendServiceName
     * @throws \Google_Service_Exception
     */
    public function deleteBackendService($backendServiceName) {

        $notReady = true;
        while ($notReady) {
            try {
                $this->computeService->backendServices->delete($this->projectId, $backendServiceName);
                $notReady = false;
            } catch (\Google_Service_Exception $e) {
                $reason = $e->getErrors()[0]["reason"] ?? null;
                if ($reason != "resourceInUseByAnotherResource") {
                    throw ($e);
                }
                sleep(1);

            }
        }

    }


    // Delete the forwarding rule
    public function deleteForwardingRule($ruleName) {
        $this->computeService->globalForwardingRules->delete($this->projectId, $ruleName);
    }

    // Delete a global address
    public function deleteGlobalAddress($addressName) {
        $this->computeService->globalAddresses->delete($this->projectId, $addressName);
    }


    public function deleteTargetProxy($name, $secure) {

        // Target Proxy
        $retry = true;
        while ($retry) {
            try {
                if ($secure)
                    $this->computeService->targetHttpsProxies->delete($this->projectId, $name);
                else
                    $this->computeService->targetHttpProxies->delete($this->projectId, $name);
                $retry = false;
            } catch (\Google_Service_Exception $e) {
                if ($e->getErrors()[0]["reason"] != "resourceInUseByAnotherResource") {
                    throw($e);
                }
            }

            sleep(1);
        }

    }


    // Delete the url map
    public function deleteUrlMap($name) {
        $retry = true;
        while ($retry) {
            try {
                $this->computeService->urlMaps->delete($this->projectId, $name);
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
