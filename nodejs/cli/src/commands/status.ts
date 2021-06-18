import Check from "./check";
import Api from "../../../core/api";
import SiteConfig from "../site-config";
import SourceManager from "../services/source-manager";
import chalk from "chalk";
import * as fs from "fs";
import SiteEntityValidator from "../services/site-entity-validator";

/**
 * Status command - runs check and also looks for any outstanding changes.
 */
export default class Status {

    private _check: Check;
    private _api: Api;
    private _siteConfig: SiteConfig;

    constructor(check?: Check, api?: Api, siteConfig?: SiteConfig) {
        this._check = check ? check : new Check();
        this._api = api ? api : Api.instance();
        this._siteConfig = siteConfig ? siteConfig : SiteConfig.instance();
    }


    /**
     * Process the status command
     */
    public async process() {

        console.log(chalk.blue("Status: ") + chalk.yellow("running .."));

        let result = await this._check.process();

        if (result) {

            // Create a new source manager
            let sourceManager = new SourceManager(this._api, this._siteConfig);

            let changes: any = sourceManager.calculateChanges();
            let validator = new SiteEntityValidator(this._siteConfig);
            validator.validateChangedEntityConfiguration(changes)
            
        }

    }


}
