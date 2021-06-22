import Config from "../core/config";
import Api from "../core/api";
import chalk from "chalk";
import {sha512} from "js-sha512";

import getMAC from 'getmac';
import Container from "../core/container";

var liveInquirer = require('inquirer');

/**
 * Authentication functions
 */
export default class AuthenticationService {

    private _config: Config;
    private _api: Api;
    private _inquirer: any;

    /**
     * Construct with a config object and inquirer
     *
     * @param config
     * @param inquirer
     */
    constructor(config?: Config, inquirer?: any, api?: any) {
        this._config = config ? config : Container.getInstance("Config");
        this._api = api ? api : new Api(this._config);
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }


    /**
     * Check authenticated
     */
    public async checkAuthenticated(reportError?: boolean): Promise<boolean> {


        if (this._config.userToken) {
            try {
                await this._api.ping();
                return true;
            } catch (e) {
                return false;
                if (reportError)
                    console.log(chalk.red(e));
            }
        } else {
            return false;
        }


    }


    /**
     * Ensure authenticated
     */
    public async ensureAuthenticated(): Promise<boolean> {

        let authenticated = await this.checkAuthenticated(true);

        if (authenticated) {
            return true;
        } else {
            return await this.login();
        }

    }


    /**
     * Login handler
     */
    public async login() {

        console.log("\nPlease login with your " + this._config.cliDisplayName + " email address and password.\n");

        let config = await this._inquirer.prompt([
            {
                "type": "input",
                "name": "emailAddress",
                "message": "Please enter your email address: "
            }, {
                "type": "password",
                "name": "password",
                "message": "Please enter your password: "
            }
        ]);

        let macAddress: string = getMAC();
        config.password = sha512(config.password + config.emailAddress);

        try {
            let userAccessToken: string = await this._api.callMethod("/cli/auth/accessToken", "POST", {}, {
                emailAddress: config.emailAddress,
                password: config.password,
                secondaryToken: macAddress
            }, "string");
            this._config.userToken = userAccessToken;
            console.log(chalk.green("\nYou have logged in successfully"));
            return true;
        } catch (error) {

            // Handle two factor case
            if (error.indexOf("two factor") > 1) {

                let twoFactorConfig = await this._inquirer.prompt([
                    {
                        "type": "input",
                        "name": "twoFactorCode",
                        "message": "Please enter the two factor code from your authenticator app"
                    }
                ]);

                try {
                    let userAccessToken: string = await this._api.callMethod("/cli/auth/accessToken", "POST", {}, {
                        emailAddress: config.emailAddress,
                        password: config.password,
                        twoFactorCode: twoFactorConfig.twoFactorCode,
                        secondaryToken: macAddress
                    }, "string");
                    this._config.userToken = userAccessToken;
                    return true;
                } catch (error) {
                    console.log(chalk.red(error));
                    return false;
                }
                ;


            } else {
                console.log(chalk.red(error));
                return false;
            }

        }


    }


    /**
     * Logout handler - simply null the user token
     */
    public logout() {
        this._config.userToken = "";
        console.log(chalk.green("\nYou have been logged out"));
    }

}
