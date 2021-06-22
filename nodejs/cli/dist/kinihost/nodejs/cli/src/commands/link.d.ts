/**
 * Create site handling class.
 */
import AuthenticationService from "../services/authentication-service";
import Api from "../core/api";
import SiteConfig from "../core/site-config";
export default class Link {
    private _authenticationService;
    private _api;
    private _inquirer;
    private _siteConfig;
    constructor(authenticationService?: AuthenticationService, siteConfig?: SiteConfig, api?: Api, inquirer?: any);
    /**
     * Check whether a site is linked.
     */
    checkLinked(): Promise<any>;
    /**
     * Ensure the site is linked
     */
    ensureLinked(): Promise<boolean>;
    /**
     * Process the link command
     */
    process(siteKey?: string): Promise<boolean>;
    private _linkSiteByKey;
}
