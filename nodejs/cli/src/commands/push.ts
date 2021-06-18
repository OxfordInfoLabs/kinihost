/**
 * Create site handling class.
 */
import Link from "./link";
import Api from "../core/api";
import SiteConfig from "../core/site-config";
import * as fs from "fs";
import chalk from "chalk";
import SourceManager from "../services/source-manager";
import ChangedObject from "../objects/changed-object";

var liveInquirer = require('inquirer');

export default class Push {

    private _api: Api;
    private _link: Link;
    private _inquirer: any;
    private _siteConfig: SiteConfig;

    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, api?: Api, inquirer?: any, siteConfig?: SiteConfig) {
        this._link = link ? link : new Link();
        this._api = api ? api : Api.instance();
        this._inquirer = inquirer ? inquirer : liveInquirer;
        this._siteConfig = siteConfig ? siteConfig : SiteConfig.instance();
    }


    /**
     * Process the push operation
     */
    public async process(): Promise<boolean> {


        // ensure linked before carrying on.
        let result = await this._link.ensureLinked();
        if (result) {

            // Ensure we have a publish directory before pushing
            if (this._siteConfig.publishDirectory) {
                return await this.__doPush();
            } else {

                let values: any = await this._inquirer.prompt([
                    {
                        "type": "input",
                        "name": "publishDirectory",
                        "default": ".",
                        "message": "Please enter the publish folder (relative to the current directory) - this will be used" +
                            " as the root for preview / published versions of your site (defaults to current directory)"
                    }]);


                let result = await this._siteConfig.updatePublishDirectory(values.publishDirectory);

                if (result) {
                    return await this.__doPush();
                } else {
                    return false;
                }


            }

        } else {
            return false;
        }


    }


    // Actually do the push once stuff has been resolved.
    public async __doPush(): Promise<boolean> {


        // Create a new source manager
        let sourceManager = new SourceManager(this._api, this._siteConfig);

        // Create validator
        let validator = new SiteEntityValidator(this._siteConfig);


        // Write the deploy config out.
        fs.writeFileSync(".kinisite-deploy", JSON.stringify(this._siteConfig.deploymentConfig));

        // Calculate changes first
        let changes: ChangedObject[] = await sourceManager.calculateChanges();

        let errors = validator.validateChangedEntityConfiguration(changes);

        // If no errors, continue to process source
        if (!errors || Object.keys(errors).length == 0) {
            let result: any = await this.__processSourceChanges(changes);
            if (fs.existsSync(".kinisite-deploy"))
                fs.unlinkSync(".kinisite-deploy");
            return result;

        } else {

            if (fs.existsSync(".kinisite-deploy"))
                fs.unlinkSync(".kinisite-deploy");

            return false;
        }


    }


    // Process source changes
    private async __processSourceChanges(changes: ChangedObject[]): Promise<boolean> {


        let sourceManager = new SourceManager(this._api, this._siteConfig);

        if (changes.length > 0) {

            console.log("\nPreparing build (this might take some time).........");

            let uploadBuild = await sourceManager.createRemoteUploadBuild(changes);

            if (Object.keys(uploadBuild.uploadUrls).length > 0) {

                console.log("\nUploading files....");

                await sourceManager.uploadFiles(uploadBuild.uploadUrls);

                await this._api.callMethod("/cli/staticwebsite/build/queue/" + uploadBuild.buildId);
                console.log(chalk.green("\nBuild #" + uploadBuild.siteBuildNumber + " has been started.  You will get an email once this has been completed"));
                return true;


            } else {
                return this._api.callMethod("/cli/staticwebsite/build/queue/" + uploadBuild.buildId).then(() => {
                    console.log(chalk.green("\nBuild #" + uploadBuild.siteBuildNumber + " has been started.  You will get an email once this has been completed"));
                    return true;
                });
            }


        } else {
            return true;
        }


    }

}
