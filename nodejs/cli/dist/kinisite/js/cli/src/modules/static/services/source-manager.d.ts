import Api from "../../../core/api";
import SiteConfig from "../site-config";
import ChangedObject from "../objects/changed-object";
import SourceUploadBuild from "../objects/source-upload-build";
/**
 * Source manager class
 */
export default class SourceManager {
    private _api;
    private _siteConfig;
    private _excludedPaths;
    /**
     * Construct source manager.
     *
     * @param api
     */
    constructor(api?: Api, siteConfig?: SiteConfig);
    /**
     * Get the object footprints as a string array for a site by key.
     */
    getRemoteObjectFootprints(): Promise<any>;
    /**
     * Get local md5 footprints for the local file set starting at the declared content root.
     */
    getLocalObjectFootprints(subDir?: string): any;
    /**
     * Return array of changed objects for an existing and new set of footprints.
     */
    generateChanges(existingFootprints: any, newFootprints: any): ChangedObject[];
    /**
     * Calculate changes by calling the above functions in sequence
     */
    calculateChanges(): Promise<ChangedObject[]>;
    /**
     * Create an upload build and retrieve the SourceUploadBuild object
     *
     * @param changes
     */
    createRemoteUploadBuild(changes: ChangedObject[]): Promise<SourceUploadBuild>;
    /**
     * Upload all files using HTTP PUT requests with progress indication
     *
     * @param uploadUrls
     */
    uploadFiles(uploadUrls: any): Promise<boolean>;
    /**
     * Upload a set of files sequentially, returning a promise which is completed
     * when all files are updated.  The bar object (if passed) should be updated on each
     * successful completion of a file.
     *
     * @param uploadUrls
     * @param bar
     */
    uploadFileSet(uploadUrls: any, bar?: any, index?: number): Promise<any>;
    /**
     * Actually upload the local file to the supplied upload url using a
     * put request.
     *
     * @param localFilename
     * @param uploadUrl
     */
    uploadFile(localFilename: string, uploadUrl: string): Promise<number>;
    /**
     * Get the remote download urls for all files identified in the array of changed objects
     * marked as UPDATE.
     *
     * @param changedObjects
     */
    getRemoteDownloadUrls(changedObjects: ChangedObject[]): Promise<any>;
    /**
     * Download all files using multiple threads with progress indication.
     *
     * @param downloadUrls
     */
    downloadFiles(downloadUrls: any): Promise<boolean>;
    /**
     * Download a set of files sequentially, updating a bar if required and return
     * true when completed.
     *
     * @param downloadUrls
     * @param bar
     * @param index
     */
    downloadFileSet(downloadUrls: any, bar?: any, index?: number): Promise<any>;
    /**
     * Download a single file to the local file location
     *
     * @param remoteUrl
     * @param localFilename
     */
    downloadFile(remoteUrl: string, localFilename: string): Promise<number>;
    /**
     * Remove local files which need deleting from an array of changed objects
     *
     * @param changedObjects
     */
    removeDeletedLocalFiles(changedObjects: ChangedObject[]): void;
    removeEmptyDirectories(parentDirectory: string): void;
    private _isSymLinkDirectory;
}
