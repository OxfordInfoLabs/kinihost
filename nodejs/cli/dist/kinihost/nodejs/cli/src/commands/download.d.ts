import Api from "../core/api";
import Link from "./link";
import SiteConfig from "../core/site-config";
export default class Download {
    private _api;
    private _link;
    private _siteConfig;
    private _inquirer;
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, api?: Api, siteConfig?: SiteConfig, inquirer?: any);
    /**
     * Process the push operation
     */
    process(): Promise<boolean>;
}
