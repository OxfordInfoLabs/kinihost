/**
 * Create site handling class.
 */
import * as fs from "fs";
import chalk from "chalk";
import Api from "../core/api";
import Config from "../core/config";
import SiteConfigSchema from "./site-config-schema";
import {ValidationError, Validator} from "jsonschema";
import Container from "./container";

export default class SiteConfig {


    // Loaded site config
    private _siteConfig: any = {};
    private _api: Api;
    private _config: Config;
    private _contentRoot: string;
    private _configFilename: string = "";

    private static _instance: SiteConfig;


    /**
     * Load the site config
     */
    constructor(api?: Api, config? : Config, contentRoot: string = ".") {
        this._api = api ? api : Container.getInstance("Api");
        this._config = config ? config : Container.getInstance("Config");
        this._contentRoot = contentRoot;
        this._loadSiteConfig();
    }


    /**
     * Set the config filename
     *
     * @param configFilename
     */
    public set configFilename(configFilename: string) {
        this._configFilename = configFilename;
        this._loadSiteConfig();
    }

    /**
     * Get the config filename
     */
    public get configFilename(): string {
        if (this._configFilename) {
            return this._configFilename;
        } else
            return "kinihost" + (this._config.mode == "production" ? "" : "-" + this._config.mode) + ".json";
    }

    /**
     * Confirm whether a site is linked.
     */
    public isSiteLinked(): boolean {
        return this.deploymentConfig.siteKey;
    }


    /**
     * Get content root.
     */
    get contentRoot(): string {
        return this._contentRoot;
    }

    /**
     * Get the site key for the current site.
     */
    public get siteKey() {
        return this.deploymentConfig.siteKey ? this.deploymentConfig.siteKey : null;
    }


    /**
     * Update the site key following a successful link
     *
     * @param siteKey
     */
    public set siteKey(siteKey) {
        this.deploymentConfig.siteKey = siteKey;
        this.siteConfig = this._siteConfig;
    }

    /**
     * Get local server config if defined.
     */
    public get localServerConfig() {
        return this._siteConfig.localServer ? this._siteConfig.localServer : {};
    }

    /**
     * Get the main config
     */
    public get deploymentConfig(): any {
        if (!this._siteConfig.deployment) {
            this._siteConfig.deployment = {};
        }
        return this._siteConfig.deployment;
    }

    /**
     * Get the publish directory
     */
    public get publishDirectory() {
        return this.deploymentConfig && this.deploymentConfig.publishDirectory ? this.deploymentConfig.publishDirectory : null;
    }

    /**
     * Set the publish directory (also update the server side)
     *
     * @param publishDirectory
     */
    public updatePublishDirectory(publishDirectory: string): Promise<boolean> {

        return new Promise<boolean>(resolve => {

            // If the file exists, continue
            if (fs.existsSync(this._contentRoot + "/" + publishDirectory)) {

                // Update the local copy once server is synced
                this.deploymentConfig.publishDirectory = publishDirectory;
                this.siteConfig = this._siteConfig;

                resolve(true);

            } else {
                console.log(chalk.red("\nThe specified publish directory does not exist under the current directory"));
                resolve(false);
            }

        });
    }

    // Load the site config
    private _loadSiteConfig() {
        this._siteConfig = {};
        if (fs.existsSync(this.configFilename)) {


            let validated: any;

            try {
                this._siteConfig = JSON.parse(fs.readFileSync(this.configFilename).toString());


                let validator = new Validator();
                validated = validator.validate(this._siteConfig, SiteConfigSchema);

            } catch (e) {
                validated = {
                    errors: [
                        {
                            stack: "instance.Malformed JSON file found"
                        }
                    ]
                };
            }

            if (validated.errors.length > 0) {
                console.log(chalk.red("\nThe site config file " + this.configFilename + " has the following validation errors:"));
                validated.errors.forEach(((error: ValidationError) => {
                    console.log(chalk.yellow(error.stack.substr(9)));
                }));
                process.exit(0);
            }
        }
    }


    public set siteConfig(value: any) {
        this._siteConfig = value;
        fs.writeFileSync(this.configFilename, JSON.stringify(value));
    }


}
