"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Changed object type
 */
var SourceUploadBuild = /** @class */ (function () {
    /**
     * Constructor
     *
     * @param buildId
     * @param uploadUrls
     */
    function SourceUploadBuild(buildId, siteBuildNumber, uploadUrls) {
        this._buildId = buildId;
        this._siteBuildNumber = siteBuildNumber;
        this._uploadUrls = uploadUrls;
    }
    Object.defineProperty(SourceUploadBuild.prototype, "buildId", {
        get: function () {
            return this._buildId;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SourceUploadBuild.prototype, "siteBuildNumber", {
        get: function () {
            return this._siteBuildNumber;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SourceUploadBuild.prototype, "uploadUrls", {
        get: function () {
            return this._uploadUrls;
        },
        enumerable: false,
        configurable: true
    });
    return SourceUploadBuild;
}());
exports.default = SourceUploadBuild;
