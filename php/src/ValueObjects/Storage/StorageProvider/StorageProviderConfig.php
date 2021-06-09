<?php


namespace Kinihost\ValueObjects\Storage\StorageProvider;

/**
 * Configuration for a storage provider.  Provides generic configuration
 * options useful for multiple providers.
 *
 * Class StorageProviderConfig
 * @package Kinihost\ValueObjects\Storage\StorageProvider
 */
class StorageProviderConfig {

    /**
     * @var bool
     */
    private $publicAccess = false;


    /**
     * Hosted site config when public access is enabled and this
     * storage is being used to host sites.
     *
     * @var HostedSiteConfig
     */
    private $hostedSiteConfig;

    /**
     * StorageProviderConfig constructor.
     *
     * @param bool $publicAccess
     * @param HostedSiteConfig $hostedSiteConfig
     */
    public function __construct($publicAccess = false, $hostedSiteConfig = null) {
        $this->publicAccess = $publicAccess;
        $this->hostedSiteConfig = $hostedSiteConfig ?? ($publicAccess ? new HostedSiteConfig() : null);
    }


    /**
     * @return bool
     */
    public function isPublicAccess() {
        return $this->publicAccess;
    }

    /**
     * @return HostedSiteConfig
     */
    public function getHostedSiteConfig() {
        return $this->hostedSiteConfig;
    }


}
