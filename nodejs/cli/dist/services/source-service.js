"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
var changed_object_1 = __importDefault(require("../objects/changed-object"));
var source_upload_build_1 = __importDefault(require("../objects/source-upload-build"));
var chalk_1 = __importDefault(require("chalk"));
var container_1 = __importDefault(require("../core/container"));
var rimraf = require("rimraf");
var fs = require('fs');
var md5 = require('md5');
var cliProgress = require('cli-progress');
var asyncRequest = require('then-request');
/**
 * Source manager class
 */
var SourceService = /** @class */ (function () {
    /**
     * Construct source manager.
     *
     * @param api
     */
    function SourceService(api, siteConfig) {
        this._excludedPaths = ["node_modules", ".git", ".svn", ".oc-cache", ".angular"];
        this._api = api ? api : container_1.default.getInstance("Api");
        this._siteConfig = siteConfig ? siteConfig : container_1.default.getInstance("SiteConfig");
    }
    /**
     * Get the object footprints as a string array for a site by key.
     */
    SourceService.prototype.getRemoteObjectFootprints = function () {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._api.callMethod("/cli/source/footprints/" + this._siteConfig.siteKey)];
                    case 1: return [2 /*return*/, _a.sent()];
                }
            });
        });
    };
    /**
     * Get local md5 footprints for the local file set starting at the declared content root.
     */
    SourceService.prototype.getLocalObjectFootprints = function (subDir) {
        var _this = this;
        if (subDir === void 0) { subDir = "/"; }
        var footprints = {};
        var dirRoot = this._siteConfig.contentRoot + subDir;
        var dir = fs.readdirSync(dirRoot);
        dir.forEach(function (entry) {
            var filename = dirRoot + "/" + entry;
            // Continue provided not excluded.
            if (_this._excludedPaths.indexOf(entry) === -1) {
                // If ignoring symlinks, skip any sym links.
                if (_this._siteConfig.deploymentConfig.ignoreSymLinks && fs.lstatSync(filename).isSymbolicLink()) {
                    return;
                }
                if (fs.lstatSync(filename).isDirectory() || _this._isSymLinkDirectory(filename)) {
                    footprints = __assign(__assign({}, footprints), _this.getLocalObjectFootprints(subDir + entry + "/"));
                }
                else {
                    footprints[(subDir + entry).substr(1)] = md5(fs.readFileSync(filename).toString());
                }
            }
        });
        return footprints;
    };
    /**
     * Return array of changed objects for an existing and new set of footprints.
     */
    SourceService.prototype.generateChanges = function (existingFootprints, newFootprints) {
        var changes = [];
        // Add any remote footprints as either updates / deletes according to their
        // local status.  Ignore files which are unchanged.
        Object.keys(existingFootprints).forEach(function (key) {
            if (newFootprints[key]) {
                if (newFootprints[key] !== existingFootprints[key])
                    changes.push(new changed_object_1.default(key, "UPDATE", newFootprints[key]));
            }
            else {
                changes.push(new changed_object_1.default(key, "DELETE", ""));
            }
        });
        // Add any new footprints as updates
        Object.keys(newFootprints).forEach(function (key) {
            if (!existingFootprints[key]) {
                changes.push(new changed_object_1.default(key, "UPDATE", newFootprints[key]));
            }
        });
        return changes;
    };
    /**
     * Calculate changes by calling the above functions in sequence
     */
    SourceService.prototype.calculateChanges = function () {
        return __awaiter(this, void 0, void 0, function () {
            var remoteFiles, localFiles, changes;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        console.log("\nCalculating changes......");
                        return [4 /*yield*/, this.getRemoteObjectFootprints()];
                    case 1:
                        remoteFiles = _a.sent();
                        localFiles = this.getLocalObjectFootprints();
                        changes = this.generateChanges(remoteFiles, localFiles);
                        if (changes.length > 0)
                            console.log(chalk_1.default.yellow(changes.length + " changed files"));
                        else
                            console.log(chalk_1.default.grey("No changed files"));
                        return [2 /*return*/, changes];
                }
            });
        });
    };
    /**
     * Create an upload build and retrieve the SourceUploadBuild object
     *
     * @param changes
     */
    SourceService.prototype.createRemoteUploadBuild = function (changes) {
        return __awaiter(this, void 0, void 0, function () {
            var uploadBuild;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._api.callMethod("/cli/source/upload/create/" + this._siteConfig.siteKey, "POST", null, changes)];
                    case 1:
                        uploadBuild = _a.sent();
                        return [2 /*return*/, new source_upload_build_1.default(uploadBuild.buildId, uploadBuild.siteBuildNumber, uploadBuild.uploadUrls)];
                }
            });
        });
    };
    /**
     * Upload all files using HTTP PUT requests with progress indication
     *
     * @param uploadUrls
     */
    SourceService.prototype.uploadFiles = function (uploadUrls) {
        var _this = this;
        return new Promise(function (resolve) {
            var multibar = new cliProgress.MultiBar({
                clearOnComplete: false,
                format: '[{bar}] {percentage}% | ETA: {eta}s | {value}/{total} | {filename}'
            }, cliProgress.Presets.shades_grey);
            var barData = [];
            var index = 0;
            Object.keys(uploadUrls).forEach(function (key) {
                if (!barData[index % 5])
                    barData[index % 5] = {};
                barData[index % 5][key] = uploadUrls[key];
                index++;
            });
            var completed = 0;
            barData.forEach(function (barItems) {
                var barMax = Object.keys(barItems).length;
                var bar = multibar.create(barMax, 0);
                _this.uploadFileSet(barItems, bar).then(function (results) {
                    bar.update(barMax);
                    completed++;
                    if (completed == barData.length) {
                        multibar.stop();
                        resolve(true);
                    }
                });
            });
        });
    };
    /**
     * Upload a set of files sequentially, returning a promise which is completed
     * when all files are updated.  The bar object (if passed) should be updated on each
     * successful completion of a file.
     *
     * @param uploadUrls
     * @param bar
     */
    SourceService.prototype.uploadFileSet = function (uploadUrls, bar, index) {
        var _this = this;
        if (index === void 0) { index = 0; }
        return new Promise(function (resolve) {
            var keys = Object.keys(uploadUrls);
            var nextKey = keys.shift();
            var returnedStati = {};
            // If we have a bar
            if (bar) {
                bar.update(index, { filename: nextKey });
            }
            _this.uploadFile(nextKey, uploadUrls[nextKey]).then(function (status) {
                // Apply the status to the returned stati
                returnedStati[nextKey] = status;
                // If more to process, do this now
                if (keys.length > 0) {
                    var remainingUrls_1 = {};
                    keys.forEach(function (key) {
                        remainingUrls_1[key] = uploadUrls[key];
                    });
                    index++;
                    _this.uploadFileSet(remainingUrls_1, bar, index).then(function (remainingStati) {
                        returnedStati = __assign(__assign({}, returnedStati), remainingStati);
                        resolve(returnedStati);
                    });
                }
                else {
                    resolve(returnedStati);
                }
            });
        });
    };
    /**
     * Actually upload the local file to the supplied upload url using a
     * put request.
     *
     * @param localFilename
     * @param uploadUrl
     */
    SourceService.prototype.uploadFile = function (localFilename, uploadUrl) {
        var _this = this;
        return new Promise(function (resolve) {
            // Ensure we qualify with api endpoint if relative url supplied
            if (uploadUrl.startsWith("/")) {
                _this._api.callMethod("/cli/upload", "PUT", null, {
                    siteKey: _this._siteConfig.siteKey,
                    url: uploadUrl,
                    body: fs.readFileSync(_this._siteConfig.contentRoot + "/" + localFilename).toString()
                }).then(function (result) {
                    resolve(200);
                });
            }
            else {
                asyncRequest("PUT", uploadUrl, { body: fs.readFileSync(_this._siteConfig.contentRoot + "/" + localFilename) }).done(function (res) {
                    resolve(res.statusCode);
                });
            }
        });
    };
    /**
     * Get the remote download urls for all files identified in the array of changed objects
     * marked as UPDATE.
     *
     * @param changedObjects
     */
    SourceService.prototype.getRemoteDownloadUrls = function (changedObjects) {
        var _this = this;
        var downloadObjects = [];
        changedObjects.forEach(function (object) {
            if (object.changeType == "UPDATE")
                downloadObjects.push(object.objectKey);
        });
        return new Promise(function (resolve) {
            _this._api.callMethod("/cli/source/download/create/" + _this._siteConfig.siteKey, "POST", null, downloadObjects).then(function (downloadUrls) {
                resolve(downloadUrls);
            });
        });
    };
    /**
     * Download all files using multiple threads with progress indication.
     *
     * @param downloadUrls
     */
    SourceService.prototype.downloadFiles = function (downloadUrls) {
        var _this = this;
        return new Promise(function (resolve) {
            var multibar = new cliProgress.MultiBar({
                clearOnComplete: false,
                format: '[{bar}] {percentage}% | ETA: {eta}s | {value}/{total} | {filename}'
            }, cliProgress.Presets.shades_grey);
            var barData = [];
            var index = 0;
            Object.keys(downloadUrls).forEach(function (key) {
                if (!barData[index % 5])
                    barData[index % 5] = {};
                barData[index % 5][key] = downloadUrls[key];
                index++;
            });
            var completed = 0;
            barData.forEach(function (barItems) {
                var barMax = Object.keys(barItems).length;
                var bar = multibar.create(barMax, 0);
                _this.downloadFileSet(barItems, bar).then(function (results) {
                    bar.update(barMax);
                    completed++;
                    if (completed == barData.length) {
                        multibar.stop();
                        resolve(true);
                    }
                });
            });
        });
    };
    /**
     * Download a set of files sequentially, updating a bar if required and return
     * true when completed.
     *
     * @param downloadUrls
     * @param bar
     * @param index
     */
    SourceService.prototype.downloadFileSet = function (downloadUrls, bar, index) {
        var _this = this;
        if (index === void 0) { index = 0; }
        return new Promise(function (resolve) {
            var keys = Object.keys(downloadUrls);
            var nextKey = keys.shift();
            var returnedStati = {};
            // If we have a bar
            if (bar) {
                bar.update(index, { filename: nextKey });
            }
            _this.downloadFile(downloadUrls[nextKey], _this._siteConfig.contentRoot + "/" + nextKey).then(function (status) {
                // Apply the status to the returned stati
                returnedStati[nextKey] = status;
                // If more to process, do this now
                if (keys.length > 0) {
                    var remainingUrls_2 = {};
                    keys.forEach(function (key) {
                        remainingUrls_2[key] = downloadUrls[key];
                    });
                    index++;
                    _this.downloadFileSet(remainingUrls_2, bar, index).then(function (remainingStati) {
                        returnedStati = __assign(__assign({}, returnedStati), remainingStati);
                        resolve(returnedStati);
                    });
                }
                else {
                    resolve(returnedStati);
                }
            });
        });
    };
    /**
     * Download a single file to the local file location
     *
     * @param remoteUrl
     * @param localFilename
     */
    SourceService.prototype.downloadFile = function (remoteUrl, localFilename) {
        return new Promise(function (resolve) {
            var directoryArray = localFilename.split("/");
            directoryArray.pop();
            if (directoryArray.length > 0) {
                var directory = directoryArray.join("/");
                fs.mkdirSync(directory, {
                    recursive: true
                });
            }
            asyncRequest("GET", remoteUrl).done(function (res) {
                fs.writeFileSync(localFilename, res.getBody());
                resolve(res.statusCode);
            });
        });
    };
    /**
     * Remove local files which need deleting from an array of changed objects
     *
     * @param changedObjects
     */
    SourceService.prototype.removeDeletedLocalFiles = function (changedObjects) {
        var _this = this;
        changedObjects.forEach(function (value) {
            if (value.changeType == "DELETE") {
                fs.unlinkSync(_this._siteConfig.contentRoot + "/" + value.objectKey);
            }
        });
        this.removeEmptyDirectories(this._siteConfig.contentRoot);
    };
    SourceService.prototype.removeEmptyDirectories = function (parentDirectory) {
        var _this = this;
        var dir = fs.readdirSync(parentDirectory);
        dir.forEach(function (entry) {
            var filename = parentDirectory + "/" + entry;
            if (fs.lstatSync(filename).isDirectory()) {
                // Firstly empty recursively
                _this.removeEmptyDirectories(filename);
                try {
                    fs.rmdirSync(filename);
                }
                catch (e) {
                    // Continue
                }
            }
        });
    };
    // Check if a sym link is a directoy
    SourceService.prototype._isSymLinkDirectory = function (filename) {
        // Check for symlink and directory
        if (fs.lstatSync(filename).isSymbolicLink()) {
            try {
                fs.readdirSync(filename);
                return true;
            }
            catch (e) {
                // Return false in this scenario
            }
        }
        return false;
    };
    return SourceService;
}());
exports.default = SourceService;
