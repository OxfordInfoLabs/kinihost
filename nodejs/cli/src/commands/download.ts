import Api from "../core/api";
import Link from "./link";
import chalk from "chalk";
import SiteConfig from "../core/site-config";
import Container from "../core/container";
import SourceService from "../services/source-service";

var liveInquirer = require('inquirer');

export default class Download {

    private _api: Api;
    private _link: Link;
    private _siteConfig: SiteConfig;
    private _inquirer: any;


    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, api?: Api, siteConfig?: SiteConfig, inquirer?: any) {
        this._link = link ? link : new Link();
        this._api = api ? api : Container.getInstance("Api");
        this._siteConfig = siteConfig ? siteConfig : Container.getInstance("SiteConfig");
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }


    /**
     * Process the push operation
     */
    public async process(): Promise<boolean> {


        // ensure linked before carrying on.
        let result = await this._link.ensureLinked();
        if (result) {

            // Create a new source manager
            let sourceManager = new SourceService(this._api, this._siteConfig);

            // Now grab.
            console.log("\nCalculating files to download......");

            let remoteFiles = await sourceManager.getRemoteObjectFootprints();
            var localFiles = sourceManager.getLocalObjectFootprints();
            var requiredDownloads = sourceManager.generateChanges(localFiles, remoteFiles);

            console.log(requiredDownloads.length + " changes required");

            if (requiredDownloads.length > 0) {

                let values = await this._inquirer.prompt([
                    {
                        "type": "confirm",
                        "name": "areYouSure",
                        "message": "This will add and remove files from your local copy, are you sure?"
                    }
                ]);

                if (values.areYouSure) {

                    console.log("\nPreparing download......");

                    let downloadUrls = await sourceManager.getRemoteDownloadUrls(requiredDownloads)

                    console.log("\nDownloading files...");

                    await sourceManager.downloadFiles(downloadUrls);

                    console.log("\nRemoving old files...");
                    sourceManager.removeDeletedLocalFiles(requiredDownloads);

                    console.log(chalk.green("\nDownload complete"));

                    return true;


                } else {
                    return false;
                }


            } else {
                return true;
            }


        } else {
            return false;
        }


    }
}
