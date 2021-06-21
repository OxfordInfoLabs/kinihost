"use strict";
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
        while (_) try {
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
var api_1 = __importDefault(require("../core/api"));
var link_1 = __importDefault(require("./link"));
var site_config_1 = __importDefault(require("../core/site-config"));
var source_manager_1 = __importDefault(require("../services/source-manager"));
var chalk_1 = __importDefault(require("chalk"));
var liveInquirer = require('inquirer');
var Download = /** @class */ (function () {
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    function Download(link, api, siteConfig, inquirer) {
        this._link = link ? link : new link_1.default();
        this._api = api ? api : api_1.default.instance();
        this._siteConfig = siteConfig ? siteConfig : site_config_1.default.instance();
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }
    /**
     * Process the push operation
     */
    Download.prototype.process = function () {
        return __awaiter(this, void 0, void 0, function () {
            var result, sourceManager, remoteFiles, localFiles, requiredDownloads, values, downloadUrls;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._link.ensureLinked()];
                    case 1:
                        result = _a.sent();
                        if (!result) return [3 /*break*/, 10];
                        sourceManager = new source_manager_1.default(this._api, this._siteConfig);
                        // Now grab.
                        console.log("\nCalculating files to download......");
                        return [4 /*yield*/, sourceManager.getRemoteObjectFootprints()];
                    case 2:
                        remoteFiles = _a.sent();
                        localFiles = sourceManager.getLocalObjectFootprints();
                        requiredDownloads = sourceManager.generateChanges(localFiles, remoteFiles);
                        console.log(requiredDownloads.length + " changes required");
                        if (!(requiredDownloads.length > 0)) return [3 /*break*/, 8];
                        return [4 /*yield*/, this._inquirer.prompt([
                                {
                                    "type": "confirm",
                                    "name": "areYouSure",
                                    "message": "This will add and remove files from your local copy, are you sure?"
                                }
                            ])];
                    case 3:
                        values = _a.sent();
                        if (!values.areYouSure) return [3 /*break*/, 6];
                        console.log("\nPreparing download......");
                        return [4 /*yield*/, sourceManager.getRemoteDownloadUrls(requiredDownloads)];
                    case 4:
                        downloadUrls = _a.sent();
                        console.log("\nDownloading files...");
                        return [4 /*yield*/, sourceManager.downloadFiles(downloadUrls)];
                    case 5:
                        _a.sent();
                        console.log("\nRemoving old files...");
                        sourceManager.removeDeletedLocalFiles(requiredDownloads);
                        console.log(chalk_1.default.green("\nDownload complete"));
                        return [2 /*return*/, true];
                    case 6: return [2 /*return*/, false];
                    case 7: return [3 /*break*/, 9];
                    case 8: return [2 /*return*/, true];
                    case 9: return [3 /*break*/, 11];
                    case 10: return [2 /*return*/, false];
                    case 11: return [2 /*return*/];
                }
            });
        });
    };
    return Download;
}());
exports.default = Download;
