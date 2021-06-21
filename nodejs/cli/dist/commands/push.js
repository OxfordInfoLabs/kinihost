"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    Object.defineProperty(o, k2, { enumerable: true, get: function() { return m[k]; } });
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
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
/**
 * Create site handling class.
 */
var link_1 = __importDefault(require("./link"));
var api_1 = __importDefault(require("../core/api"));
var site_config_1 = __importDefault(require("../core/site-config"));
var fs = __importStar(require("fs"));
var chalk_1 = __importDefault(require("chalk"));
var source_manager_1 = __importDefault(require("../services/source-manager"));
var liveInquirer = require('inquirer');
var Push = /** @class */ (function () {
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    function Push(link, api, inquirer, siteConfig) {
        this._link = link ? link : new link_1.default();
        this._api = api ? api : api_1.default.instance();
        this._inquirer = inquirer ? inquirer : liveInquirer;
        this._siteConfig = siteConfig ? siteConfig : site_config_1.default.instance();
    }
    /**
     * Process the push operation
     */
    Push.prototype.process = function () {
        return __awaiter(this, void 0, void 0, function () {
            var result, values, result_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._link.ensureLinked()];
                    case 1:
                        result = _a.sent();
                        if (!result) return [3 /*break*/, 9];
                        if (!this._siteConfig.publishDirectory) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.__doPush()];
                    case 2: return [2 /*return*/, _a.sent()];
                    case 3: return [4 /*yield*/, this._inquirer.prompt([
                            {
                                "type": "input",
                                "name": "publishDirectory",
                                "default": ".",
                                "message": "Please enter the publish folder (relative to the current directory) - this will be used" +
                                    " as the root for preview / published versions of your site (defaults to current directory)"
                            }
                        ])];
                    case 4:
                        values = _a.sent();
                        return [4 /*yield*/, this._siteConfig.updatePublishDirectory(values.publishDirectory)];
                    case 5:
                        result_1 = _a.sent();
                        if (!result_1) return [3 /*break*/, 7];
                        return [4 /*yield*/, this.__doPush()];
                    case 6: return [2 /*return*/, _a.sent()];
                    case 7: return [2 /*return*/, false];
                    case 8: return [3 /*break*/, 10];
                    case 9: return [2 /*return*/, false];
                    case 10: return [2 /*return*/];
                }
            });
        });
    };
    // Actually do the push once stuff has been resolved.
    Push.prototype.__doPush = function () {
        return __awaiter(this, void 0, void 0, function () {
            var sourceManager, validator, changes, errors, result;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        sourceManager = new source_manager_1.default(this._api, this._siteConfig);
                        validator = new SiteEntityValidator(this._siteConfig);
                        // Write the deploy config out.
                        fs.writeFileSync(".kinisite-deploy", JSON.stringify(this._siteConfig.deploymentConfig));
                        return [4 /*yield*/, sourceManager.calculateChanges()];
                    case 1:
                        changes = _a.sent();
                        errors = validator.validateChangedEntityConfiguration(changes);
                        if (!(!errors || Object.keys(errors).length == 0)) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.__processSourceChanges(changes)];
                    case 2:
                        result = _a.sent();
                        if (fs.existsSync(".kinisite-deploy"))
                            fs.unlinkSync(".kinisite-deploy");
                        return [2 /*return*/, result];
                    case 3:
                        if (fs.existsSync(".kinisite-deploy"))
                            fs.unlinkSync(".kinisite-deploy");
                        return [2 /*return*/, false];
                }
            });
        });
    };
    // Process source changes
    Push.prototype.__processSourceChanges = function (changes) {
        return __awaiter(this, void 0, void 0, function () {
            var sourceManager, uploadBuild_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        sourceManager = new source_manager_1.default(this._api, this._siteConfig);
                        if (!(changes.length > 0)) return [3 /*break*/, 6];
                        console.log("\nPreparing build (this might take some time).........");
                        return [4 /*yield*/, sourceManager.createRemoteUploadBuild(changes)];
                    case 1:
                        uploadBuild_1 = _a.sent();
                        if (!(Object.keys(uploadBuild_1.uploadUrls).length > 0)) return [3 /*break*/, 4];
                        console.log("\nUploading files....");
                        return [4 /*yield*/, sourceManager.uploadFiles(uploadBuild_1.uploadUrls)];
                    case 2:
                        _a.sent();
                        return [4 /*yield*/, this._api.callMethod("/cli/staticwebsite/build/queue/" + uploadBuild_1.buildId)];
                    case 3:
                        _a.sent();
                        console.log(chalk_1.default.green("\nBuild #" + uploadBuild_1.siteBuildNumber + " has been started.  You will get an email once this has been completed"));
                        return [2 /*return*/, true];
                    case 4: return [2 /*return*/, this._api.callMethod("/cli/staticwebsite/build/queue/" + uploadBuild_1.buildId).then(function () {
                            console.log(chalk_1.default.green("\nBuild #" + uploadBuild_1.siteBuildNumber + " has been started.  You will get an email once this has been completed"));
                            return true;
                        })];
                    case 5: return [3 /*break*/, 7];
                    case 6: return [2 /*return*/, true];
                    case 7: return [2 /*return*/];
                }
            });
        });
    };
    return Push;
}());
exports.default = Push;
