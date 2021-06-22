import Api from "../core/api";
import SiteConfig from "../core/site-config";
import ChangedObject from "../objects/changed-object";
import SourceUploadBuild from "../objects/source-upload-build";
import chalk from "chalk";
import Container from "../core/container";

const rimraf = require("rimraf");
const fs = require('fs');
const md5 = require('md5');
const cliProgress = require('cli-progress');
const asyncRequest = require('then-request');

/**
 * Source manager class
 */
export default class SourceService {

    // API
    private _api: Api;

    // Site config
    private _siteConfig: SiteConfig;

    private _excludedPaths = ["node_modules", ".git", ".svn", ".oc-cache"];

    /**
     * Construct source manager.
     *
     * @param api
     */
    constructor(api?: Api, siteConfig?: SiteConfig) {
        this._api = api ? api : Container.getInstance("Api")
        this._siteConfig = siteConfig ? siteConfig : Container.getInstance("SiteConfig");
    }


    /**
     * Get the object footprints as a string array for a site by key.
     */
    public async getRemoteObjectFootprints(): Promise<any> {
        return await this._api.callMethod("/cli/source/footprints/" + this._siteConfig.siteKey);
    }


    /**
     * Get local md5 footprints for the local file set starting at the declared content root.
     */
    public getLocalObjectFootprints(subDir: string = "/"): any {

        let footprints: any = {};

        const dirRoot = this._siteConfig.contentRoot + subDir;

        let dir = fs.readdirSync(dirRoot);

        dir.forEach((entry: string) => {
            const filename: string = dirRoot + "/" + entry;

            // Continue provided not excluded.
            if (this._excludedPaths.indexOf(entry) === -1) {

                // If ignoring symlinks, skip any sym links.
                if (this._siteConfig.deploymentConfig.ignoreSymLinks && fs.lstatSync(filename).isSymbolicLink()) {
                    return;
                }

                if (fs.lstatSync(filename).isDirectory() || this._isSymLinkDirectory(filename)) {
                    footprints = {...footprints, ...this.getLocalObjectFootprints(subDir + entry + "/")};
                } else {
                    footprints[(subDir + entry).substr(1)] = md5(fs.readFileSync(filename).toString());
                }
            }
        });

        return footprints;

    }


    /**
     * Return array of changed objects for an existing and new set of footprints.
     */
    public generateChanges(existingFootprints: any, newFootprints: any): ChangedObject[] {

        let changes: ChangedObject[] = [];

        // Add any remote footprints as either updates / deletes according to their
        // local status.  Ignore files which are unchanged.
        Object.keys(existingFootprints).forEach(key => {
            if (newFootprints[key]) {
                if (newFootprints[key] !== existingFootprints[key])
                    changes.push(new ChangedObject(key, "UPDATE", newFootprints[key]));
            } else {
                changes.push(new ChangedObject(key, "DELETE", ""));
            }
        });

        // Add any new footprints as updates
        Object.keys(newFootprints).forEach(key => {
            if (!existingFootprints[key]) {
                changes.push(new ChangedObject(key, "UPDATE", newFootprints[key]));
            }
        });


        return changes;
    }


    /**
     * Calculate changes by calling the above functions in sequence
     */
    public async calculateChanges(): Promise<ChangedObject[]> {

        console.log("\nCalculating changes......");

        let remoteFiles: any = await this.getRemoteObjectFootprints();

        // Get local footprints for comparison
        var localFiles = this.getLocalObjectFootprints();

        // Create the changes array
        let changes = this.generateChanges(remoteFiles, localFiles);

        if (changes.length > 0)
            console.log(chalk.yellow(changes.length + " changed files"));
        else
            console.log(chalk.grey("No changed files"));

        return changes;

    }

    /**
     * Create an upload build and retrieve the SourceUploadBuild object
     *
     * @param changes
     */
    public async createRemoteUploadBuild(changes: ChangedObject[]) {
        let uploadBuild: any = await this._api.callMethod("/cli/source/upload/create/" + this._siteConfig.siteKey, "POST", null, changes);
        return new SourceUploadBuild(uploadBuild.buildId, uploadBuild.siteBuildNumber, uploadBuild.uploadUrls);
    }


    /**
     * Upload all files using HTTP PUT requests with progress indication
     *
     * @param uploadUrls
     */
    public uploadFiles(uploadUrls: any): Promise<boolean> {

        return new Promise<boolean>(resolve => {

            const multibar = new cliProgress.MultiBar({
                clearOnComplete: false,
                format: '[{bar}] {percentage}% | ETA: {eta}s | {value}/{total} | {filename}'
            }, cliProgress.Presets.shades_grey);


            let barData: any[] = [];
            let index = 0;
            Object.keys(uploadUrls).forEach((key) => {
                if (!barData[index % 5])
                    barData[index % 5] = {};

                barData[index % 5][key] = uploadUrls[key];
                index++;
            });


            let completed = 0;

            barData.forEach((barItems) => {

                let barMax = Object.keys(barItems).length;

                let bar = multibar.create(barMax, 0);

                this.uploadFileSet(barItems, bar).then((results) => {
                    bar.update(barMax);
                    completed++;

                    if (completed == barData.length) {
                        multibar.stop();
                        resolve(true);
                    }
                })
            });

        });


    }


    /**
     * Upload a set of files sequentially, returning a promise which is completed
     * when all files are updated.  The bar object (if passed) should be updated on each
     * successful completion of a file.
     *
     * @param uploadUrls
     * @param bar
     */
    public uploadFileSet(uploadUrls: any, bar?: any, index: number = 0): Promise<any> {

        return new Promise<any>(resolve => {

            let keys = Object.keys(uploadUrls);
            let nextKey: any = keys.shift();

            let returnedStati: any = {};

            // If we have a bar
            if (bar) {
                bar.update(index, {filename: nextKey});
            }

            this.uploadFile(nextKey, uploadUrls[nextKey]).then(status => {


                // Apply the status to the returned stati
                returnedStati[nextKey] = status;

                // If more to process, do this now
                if (keys.length > 0) {

                    let remainingUrls: any = {};
                    keys.forEach(key => {
                        remainingUrls[key] = uploadUrls[key];
                    });

                    index++;

                    this.uploadFileSet(remainingUrls, bar, index).then((remainingStati: any) => {
                        returnedStati = {...returnedStati, ...remainingStati};
                        resolve(returnedStati);
                    });


                } else {
                    resolve(returnedStati);
                }

            });

        });

    }


    /**
     * Actually upload the local file to the supplied upload url using a
     * put request.
     *
     * @param localFilename
     * @param uploadUrl
     */
    public uploadFile(localFilename: string, uploadUrl: string): Promise<number> {

        return new Promise<number>(resolve => {

            asyncRequest("PUT", uploadUrl, {body: fs.readFileSync(this._siteConfig.contentRoot + "/" + localFilename)}).done((res: any) => {
                resolve(res.statusCode);
            });


        });
    }


    /**
     * Get the remote download urls for all files identified in the array of changed objects
     * marked as UPDATE.
     *
     * @param changedObjects
     */
    public getRemoteDownloadUrls(changedObjects: ChangedObject[]): Promise<any> {

        let downloadObjects: any = [];

        changedObjects.forEach(object => {
            if (object.changeType == "UPDATE")
                downloadObjects.push(object.objectKey);
        });

        return new Promise<any>(resolve => {

            this._api.callMethod("/cli/source/download/create/" + this._siteConfig.siteKey, "POST", null, downloadObjects).then((downloadUrls: any) => {
                resolve(downloadUrls);
            });

        });
    }


    /**
     * Download all files using multiple threads with progress indication.
     *
     * @param downloadUrls
     */
    public downloadFiles(downloadUrls: any): Promise<boolean> {

        return new Promise<boolean>(resolve => {

            const multibar = new cliProgress.MultiBar({
                clearOnComplete: false,
                format: '[{bar}] {percentage}% | ETA: {eta}s | {value}/{total} | {filename}'
            }, cliProgress.Presets.shades_grey);


            let barData: any[] = [];
            let index = 0;
            Object.keys(downloadUrls).forEach((key) => {
                if (!barData[index % 5])
                    barData[index % 5] = {};

                barData[index % 5][key] = downloadUrls[key];
                index++;
            });


            let completed = 0;

            barData.forEach((barItems) => {

                let barMax = Object.keys(barItems).length;

                let bar = multibar.create(barMax, 0);

                this.downloadFileSet(barItems, bar).then((results) => {
                    bar.update(barMax);
                    completed++;

                    if (completed == barData.length) {
                        multibar.stop();
                        resolve(true);
                    }
                })
            });

        });


    }


    /**
     * Download a set of files sequentially, updating a bar if required and return
     * true when completed.
     *
     * @param downloadUrls
     * @param bar
     * @param index
     */
    public downloadFileSet(downloadUrls: any, bar?: any, index: number = 0): Promise<any> {

        return new Promise<any>(resolve => {

            let keys = Object.keys(downloadUrls);
            let nextKey: any = keys.shift();

            let returnedStati: any = {};

            // If we have a bar
            if (bar) {
                bar.update(index, {filename: nextKey});
            }

            this.downloadFile(downloadUrls[nextKey], this._siteConfig.contentRoot + "/" + nextKey).then(status => {


                // Apply the status to the returned stati
                returnedStati[nextKey] = status;

                // If more to process, do this now
                if (keys.length > 0) {

                    let remainingUrls: any = {};
                    keys.forEach(key => {
                        remainingUrls[key] = downloadUrls[key];
                    });

                    index++;

                    this.downloadFileSet(remainingUrls, bar, index).then((remainingStati: any) => {
                        returnedStati = {...returnedStati, ...remainingStati};
                        resolve(returnedStati);
                    });


                } else {
                    resolve(returnedStati);
                }

            });


        });

    }


    /**
     * Download a single file to the local file location
     *
     * @param remoteUrl
     * @param localFilename
     */
    public downloadFile(remoteUrl: string, localFilename: string): Promise<number> {

        return new Promise<any>(resolve => {

            let directoryArray = localFilename.split("/");
            directoryArray.pop();
            if (directoryArray.length > 0) {
                let directory = directoryArray.join("/");
                fs.mkdirSync(directory, {
                    recursive: true
                })
            }


            asyncRequest("GET", remoteUrl).done((res: any) => {
                fs.writeFileSync(localFilename, res.getBody());
                resolve(res.statusCode);
            });


        });
    }


    /**
     * Remove local files which need deleting from an array of changed objects
     *
     * @param changedObjects
     */
    public removeDeletedLocalFiles(changedObjects: ChangedObject[]) {
        changedObjects.forEach(value => {

            if (value.changeType == "DELETE") {
                fs.unlinkSync(this._siteConfig.contentRoot + "/" + value.objectKey);
            }
        });

        this.removeEmptyDirectories(this._siteConfig.contentRoot);
    }


    public removeEmptyDirectories(parentDirectory: string) {

        let dir = fs.readdirSync(parentDirectory);

        dir.forEach((entry: string) => {

            const filename: string = parentDirectory + "/" + entry;

            if (fs.lstatSync(filename).isDirectory()) {

                // Firstly empty recursively
                this.removeEmptyDirectories(filename);

                try {
                    fs.rmdirSync(filename);
                } catch (e) {
                    // Continue
                }
            }

        });
    }

    // Check if a sym link is a directoy
    private _isSymLinkDirectory(filename: string) {

        // Check for symlink and directory
        if (fs.lstatSync(filename).isSymbolicLink()) {
            try {
                fs.readdirSync(filename);
                return true;
            } catch (e) {
                // Return false in this scenario
            }
        }

        return false;
    }


}
