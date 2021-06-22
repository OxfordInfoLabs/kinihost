/**
 * Create site handling class.
 */
import Link from "./link";
import Api from "../core/api";
import SiteConfig from "../core/site-config";
export default class Push {
    private _api;
    private _link;
    private _inquirer;
    private _siteConfig;
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, api?: Api, inquirer?: any, siteConfig?: SiteConfig);
    /**
     * Process the push operation
     */
    process(): Promise<boolean>;
    __doPush(): Promise<boolean>;
    private __processSourceChanges;
}
