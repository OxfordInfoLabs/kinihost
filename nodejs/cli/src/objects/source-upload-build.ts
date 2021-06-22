/**
 * Changed object type
 */
export default class SourceUploadBuild {

    /**
     * Id of the build created for this source upload
     */
    private _buildId: string;

    /**
     * The sequential build number for the site.
     */
    private _siteBuildNumber: string;

    /**
     * Indexed upload urls keyed in by the local filenames which need uploading.
     */
    private _uploadUrls: any;


    /**
     * Constructor
     *
     * @param buildId
     * @param uploadUrls
     */
    constructor(buildId: string, siteBuildNumber: string, uploadUrls: any) {
        this._buildId = buildId;
        this._siteBuildNumber = siteBuildNumber;
        this._uploadUrls = uploadUrls;
    }

    get buildId(): string {
        return this._buildId;
    }


    get siteBuildNumber(): string {
        return this._siteBuildNumber;
    }

    get uploadUrls(): any {
        return this._uploadUrls;
    }
}
