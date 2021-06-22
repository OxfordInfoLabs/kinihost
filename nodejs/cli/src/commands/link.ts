/**
 * Create site handling class.
 */
import AuthenticationService from "../services/authentication-service";
import Api from "../core/api";
import chalk from "chalk";
import SiteConfig from "../core/site-config";
import Container from "../core/container";

var liveInquirer = require('inquirer');

export default class Link {

    private _authenticationService: AuthenticationService;
    private _api: Api;
    private _inquirer: any;
    private _siteConfig: SiteConfig;

    constructor(authenticationService?: AuthenticationService, siteConfig?: SiteConfig, api?: Api, inquirer?: any) {
        this._authenticationService = authenticationService ? authenticationService : Container.getInstance("AuthenticationService");
        this._siteConfig = siteConfig ? siteConfig : Container.getInstance("SiteConfig");
        this._api = api ? api : Container.getInstance("Api");
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }


    /**
     * Check whether a site is linked.
     */
    public async checkLinked(): Promise<any> {

        if (this._siteConfig.isSiteLinked()) {

            let siteKey = this._siteConfig.siteKey;

            try {
                let site = await this._api.callMethod("/cli/site/" + siteKey);
                return site;
            } catch (error) {
                console.log(chalk.red(error));
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * Ensure the site is linked
     */
    public async ensureLinked(): Promise<boolean> {

        let authenticated = await this._authenticationService.ensureAuthenticated();
        if (authenticated) {
            let linked = await this.checkLinked();

            if (linked) {
                return true;
            } else {
                return await this.process();
            }

        } else {
            return false;
        }


    }


    /**
     * Process the link command
     */
    public async process(siteKey = ""): Promise<boolean> {

        let authenticated = await this._authenticationService.ensureAuthenticated();

        if (authenticated) {

            // If explicit site key passed, process this now otherwise fail.
            if (siteKey) {

                let result = await this._linkSiteByKey(siteKey)

                if (result)
                    console.log(chalk.green("\nThe current site has now been linked to " + siteKey));

                return result;


            } else {

                /**
                 * Call the site method
                 */
                let results: any[] = await this._api.callMethod("/cli/site");

                if (results.length > 0) {

                    let choices: any[] = [];
                    results.forEach((value) => {
                        choices.push({name: value.title, value: value.siteKey});
                    });

                    let inquirerResults: any = await this._inquirer.prompt([
                        {
                            "type": "list",
                            "message": "Please select a site to link to the current directory",
                            "name": "siteKey",
                            "choices": choices
                        }
                    ]);

                    let result = await this._linkSiteByKey(inquirerResults.siteKey);

                    if (result)
                        console.log(chalk.green("\nThe current site has now been linked to " + inquirerResults.siteKey));

                    return result;


                } else {
                    console.log(chalk.red("\nYou currently have no active sites within your account."));
                    return false;
                }


            }


        } else {
            return false;
        }


    }


// Actually do a link
    private async _linkSiteByKey(siteKey: string): Promise<boolean> {
        // Grab the site to confirm access.
        try {
            await this._api.callMethod("/cli/site/" + siteKey);
            this._siteConfig.siteKey = siteKey;
            return true;
        } catch (e) {
            console.log(chalk.red(e));
            return false;
        }

    }


}
