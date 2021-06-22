import Check from "./check";
import chalk from "chalk";
import Api from "../core/api";
import SiteConfig from "../core/site-config";
import Container from "../core/container";
import SourceService from "../services/source-service";


/**
 * Status command - runs check and also looks for any outstanding changes.
 */
export default class Status {

    private _check: Check;
    private _api: Api;
    private _siteConfig: SiteConfig;

    constructor(check?: Check, api?: Api, siteConfig?: SiteConfig) {
        this._check = check ? check : new Check();
        this._api = api ? api : Container.getInstance("Api");
        this._siteConfig = siteConfig ? siteConfig : Container.getInstance("SiteConfig");
    }


    /**
     * Process the status command
     */
    public async process() {

        console.log(chalk.blue("Status: ") + chalk.yellow("running .."));

        let result = await this._check.process();

        if (result) {

            // Create a new source manager
            let sourceManager = new SourceService(this._api, this._siteConfig);
            sourceManager.calculateChanges();

        }

    }


}
