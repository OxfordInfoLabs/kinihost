import Api from "../core/api";
import Config from "../core/config";
export default class SiteConfig {
    private _siteConfig;
    private _api;
    private _config;
    private _contentRoot;
    private _configFilename;
    private static _instance;
    /**
     * Load the site config
     */
    constructor(api?: Api, config?: Config, contentRoot?: string);
    /**
     * Get the config filename
     */
    get configFilename(): string;
    /**
     * Confirm whether a site is linked.
     */
    isSiteLinked(): boolean;
    /**
     * Get content root.
     */
    get contentRoot(): string;
    /**
     * Get the site key for the current site.
     */
    get siteKey(): any;
    /**
     * Update the site key following a successful link
     *
     * @param siteKey
     */
    set siteKey(siteKey: any);
    /**
     * Get local server config if defined.
     */
    get localServerConfig(): any;
    /**
     * Get the main config
     */
    get deploymentConfig(): any;
    /**
     * Get the publish directory
     */
    get publishDirectory(): any;
    /**
     * Set the publish directory (also update the server side)
     *
     * @param publishDirectory
     */
    updatePublishDirectory(publishDirectory: string): Promise<boolean>;
    private _loadSiteConfig;
    set siteConfig(value: any);
}
