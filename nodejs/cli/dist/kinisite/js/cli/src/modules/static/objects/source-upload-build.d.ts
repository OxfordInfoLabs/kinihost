/**
 * Changed object type
 */
export default class SourceUploadBuild {
    /**
     * Id of the build created for this source upload
     */
    private _buildId;
    /**
     * The sequential build number for the site.
     */
    private _siteBuildNumber;
    /**
     * Indexed upload urls keyed in by the local filenames which need uploading.
     */
    private _uploadUrls;
    /**
     * Constructor
     *
     * @param buildId
     * @param uploadUrls
     */
    constructor(buildId: string, siteBuildNumber: string, uploadUrls: any);
    get buildId(): string;
    get siteBuildNumber(): string;
    get uploadUrls(): any;
}
