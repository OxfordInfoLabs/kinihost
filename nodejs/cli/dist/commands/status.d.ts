import Check from "./check";
import Api from "../core/api";
import SiteConfig from "../core/site-config";
/**
 * Status command - runs check and also looks for any outstanding changes.
 */
export default class Status {
    private _check;
    private _api;
    private _siteConfig;
    constructor(check?: Check, api?: Api, siteConfig?: SiteConfig);
    /**
     * Process the status command
     */
    process(): Promise<void>;
}
