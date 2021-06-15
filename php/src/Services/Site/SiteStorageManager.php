<?php

namespace Kinihost\Services\Site;

use Kinikit\Core\Configuration\Configuration;
use Kinihost\Services\Storage\StorageRoot;
use Kinihost\Services\Storage\VersionedStorageRoot;
use Kinihost\ValueObjects\Storage\StorageProvider\HostedSiteConfig;
use Kinihost\ValueObjects\Storage\StorageProvider\StorageProviderConfig;
use Kinihost\Objects\Site\Site;
use Kinihost\Objects\Site\SiteDomain;

/**
 * Extension of the versioned storage root for managing access to source storage for a site.
 *
 * Class SiteStorageManager
 * @package Kinihost\Objects\Site\StorageRoot
 */
class SiteStorageManager {

    /**
     * Create storage for a new site - use the defaults for this
     *
     * @param Site $site
     */
    public function createStorage($site) {

        // Create storage for source, production and stage
        ($this->getContentRoot($site))->create();
        ($this->getPreviewRoot($site))->create(new StorageProviderConfig(true));
        ($this->getProductionRoot($site))->create(new StorageProviderConfig(true));

        return $this->getStorageKeyAndContainerKey($site, "test")[0];


    }


    /**
     * Update storage based upon the passed site.  This is called in the scenario that
     * index and error pages and / or routing changes have occurred which require a change of bucket.
     *
     * @param Site $site
     */
    public function updateStorage($site) {

        // If we are in a single domain scenario we want to rename our storage if possible to allow for providers
        // which are enabled for direct domain mapping.

        if ($site->getType() == Site::TYPE_SITE && sizeof($site->getSiteDomains()) < 2) {

            list($storageProviderKey, $defaultProductionDomain) = $this->getStorageKeyAndContainerKey($site, "production");

            // Get the old primary domain
            $currentPrimaryDomain = $site->getProviderSettings()["primaryDomain"] ?? $defaultProductionDomain;
            $newPrimaryDomain = ($site->getSiteDomains()[0] ?? new SiteDomain($defaultProductionDomain))->getDomainName();

            if ($currentPrimaryDomain != $newPrimaryDomain) {

                // Keep reference to current production root.
                $currentProductionRoot = $this->getProductionRoot($site);

                // Update provider settings
                $site->setProviderSetting("primaryDomain", $newPrimaryDomain);

                // Grab new production root
                $newProductionRoot = $this->getProductionRoot($site);

                try {
                    $newProductionRoot->create(new StorageProviderConfig(true));

                    // Save the site
                    $site->save();

                    // Synchronise the new production root
                    $newProductionRoot->synchronise($currentProductionRoot);

                    // Remove the old production root
                    $currentProductionRoot->remove();

                } catch (\Exception $e) {

                    echo $e->getMessage();

                    // Update provider settings
                    $site->setProviderSetting("primaryDomain", $currentPrimaryDomain);

                }

            }

        }

        $storageConfig = new StorageProviderConfig(true, new HostedSiteConfig($site->getConfig()->getIndexPage(),
            $site->getConfig()->getNotFoundPage()));

        ($this->getPreviewRoot($site))->update($storageConfig);

        if ($site->getType() == Site::TYPE_SITE)
            ($this->getProductionRoot($site))->update($storageConfig);

    }


    /**
     * Get the root for the site source
     *
     * @param Site $site
     *
     * @return VersionedStorageRoot
     */
    public function getContentRoot($site) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, "content");
        $storageRoot = new VersionedStorageRoot($storageProviderKey, $containerKey);
        $storageRoot->getStorageProvider()->setCaching(false);
        return $storageRoot;
    }


    /**
     * Get the upload root for the site source.  This is mapped to the same physical space as the content root for
     * efficiency of copying files around.
     *
     * @param $site
     * @return StorageRoot
     */
    public function getUploadRoot($site) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, "content");
        return new StorageRoot($storageProviderKey, $containerKey, "upload");
    }


    /**
     * Get a processing root for this site
     *
     * @param $site
     * @return StorageRoot
     */
    public function getProcessingRoot($site) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, "content");
        return new StorageRoot($storageProviderKey, $containerKey, "processing/" . $site->getLastBuildNumber());
    }


    /**
     * Get a storage root for a published version of a site - only used for components
     *
     * @param $site
     * @param $version
     *
     * @return StorageRoot
     */
    public function getPublishedVersionRoot($site, $version) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, "content");
        return new StorageRoot($storageProviderKey, $containerKey, "published/" . $version);
    }


    /**
     * Get the media root for the passed site
     *
     * @param $site
     */
    public function getMediaRoot($site) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, "content");
        return new StorageRoot($storageProviderKey, $containerKey, "media");

    }


    /**
     * Convenience wrapper to the deployment root function for the standard production
     * storage.
     *
     * @param Site $site
     * @return StorageRoot
     */
    public function getProductionRoot($site) {
        list($storageProviderKey) = $this->getStorageKeyAndContainerKey($site, "production");

        // If a primary domain has been set, return the primary root.
        if (isset($site->getProviderSettings()["primaryDomain"])) {
            return new StorageRoot($storageProviderKey, $site->getProviderSettings()["primaryDomain"]);
        } else {
            return $this->getDeploymentRoot($site, "production");
        }
    }


    /**
     * Convenience wrapper to the deployment root function for the standard preview storage.
     *
     * @param $site
     * @return StorageRoot
     */
    public function getPreviewRoot($site) {
        $root = $this->getDeploymentRoot($site, "preview");
        $root->getStorageProvider()->setCaching(false);
        return $root;
    }


    /**
     * Get a deployment root for a given identified deployment
     *
     * @param Site $site
     * @param string $deploymentKey
     *
     * @return StorageRoot
     *
     */
    public function getDeploymentRoot($site, $deploymentKey) {
        list($storageProviderKey, $containerKey) = $this->getStorageKeyAndContainerKey($site, $deploymentKey);
        return new StorageRoot($storageProviderKey, $containerKey);
    }


    /**
     * Get the container key from a site
     *
     * @param Site $site
     * @param $containerType
     * @return array
     */
    private function getStorageKeyAndContainerKey($site, $containerType) {
        $storageKey = $site->getStorageProviderKey() ?? Configuration::readParameter("kinihost.storage.provider");
        $serviceDomain = $site->getServiceDomain() ?? Configuration::readParameter("kinihost.service.domain");
        $containerType = $site->getSiteKey() . "-" . $containerType . "." . $serviceDomain;
        return [$storageKey, $containerType];
    }

}
