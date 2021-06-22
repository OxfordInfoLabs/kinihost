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
var chalk_1 = __importDefault(require("chalk"));
var container_1 = __importDefault(require("../core/container"));
var liveInquirer = require('inquirer');
var Link = /** @class */ (function () {
    function Link(authenticationService, siteConfig, api, inquirer) {
        this._authenticationService = authenticationService ? authenticationService : container_1.default.getInstance("AuthenticationService");
        this._siteConfig = siteConfig ? siteConfig : container_1.default.getInstance("SiteConfig");
        this._api = api ? api : container_1.default.getInstance("Api");
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }
    /**
     * Check whether a site is linked.
     */
    Link.prototype.checkLinked = function () {
        return __awaiter(this, void 0, void 0, function () {
            var siteKey, site, error_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!this._siteConfig.isSiteLinked()) return [3 /*break*/, 5];
                        siteKey = this._siteConfig.siteKey;
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 3, , 4]);
                        return [4 /*yield*/, this._api.callMethod("/cli/site/" + siteKey)];
                    case 2:
                        site = _a.sent();
                        return [2 /*return*/, site];
                    case 3:
                        error_1 = _a.sent();
                        console.log(chalk_1.default.red(error_1));
                        return [2 /*return*/, false];
                    case 4: return [3 /*break*/, 6];
                    case 5: return [2 /*return*/, false];
                    case 6: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Ensure the site is linked
     */
    Link.prototype.ensureLinked = function () {
        return __awaiter(this, void 0, void 0, function () {
            var authenticated, linked;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._authenticationService.ensureAuthenticated()];
                    case 1:
                        authenticated = _a.sent();
                        if (!authenticated) return [3 /*break*/, 6];
                        return [4 /*yield*/, this.checkLinked()];
                    case 2:
                        linked = _a.sent();
                        if (!linked) return [3 /*break*/, 3];
                        return [2 /*return*/, true];
                    case 3: return [4 /*yield*/, this.process()];
                    case 4: return [2 /*return*/, _a.sent()];
                    case 5: return [3 /*break*/, 7];
                    case 6: return [2 /*return*/, false];
                    case 7: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Process the link command
     */
    Link.prototype.process = function (siteKey) {
        if (siteKey === void 0) { siteKey = ""; }
        return __awaiter(this, void 0, void 0, function () {
            var authenticated, result, results, choices_1, inquirerResults, result;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._authenticationService.ensureAuthenticated()];
                    case 1:
                        authenticated = _a.sent();
                        if (!authenticated) return [3 /*break*/, 9];
                        if (!siteKey) return [3 /*break*/, 3];
                        return [4 /*yield*/, this._linkSiteByKey(siteKey)];
                    case 2:
                        result = _a.sent();
                        if (result)
                            console.log(chalk_1.default.green("\nThe current site has now been linked to " + siteKey));
                        return [2 /*return*/, result];
                    case 3: return [4 /*yield*/, this._api.callMethod("/cli/site")];
                    case 4:
                        results = _a.sent();
                        if (!(results.length > 0)) return [3 /*break*/, 7];
                        choices_1 = [];
                        results.forEach(function (value) {
                            choices_1.push({ name: value.title, value: value.siteKey });
                        });
                        return [4 /*yield*/, this._inquirer.prompt([
                                {
                                    "type": "list",
                                    "message": "Please select a site to link to the current directory",
                                    "name": "siteKey",
                                    "choices": choices_1
                                }
                            ])];
                    case 5:
                        inquirerResults = _a.sent();
                        return [4 /*yield*/, this._linkSiteByKey(inquirerResults.siteKey)];
                    case 6:
                        result = _a.sent();
                        if (result)
                            console.log(chalk_1.default.green("\nThe current site has now been linked to " + inquirerResults.siteKey));
                        return [2 /*return*/, result];
                    case 7:
                        console.log(chalk_1.default.red("\nYou currently have no active sites within your account."));
                        return [2 /*return*/, false];
                    case 8: return [3 /*break*/, 10];
                    case 9: return [2 /*return*/, false];
                    case 10: return [2 /*return*/];
                }
            });
        });
    };
    // Actually do a link
    Link.prototype._linkSiteByKey = function (siteKey) {
        return __awaiter(this, void 0, void 0, function () {
            var e_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        _a.trys.push([0, 2, , 3]);
                        return [4 /*yield*/, this._api.callMethod("/cli/site/" + siteKey)];
                    case 1:
                        _a.sent();
                        this._siteConfig.siteKey = siteKey;
                        return [2 /*return*/, true];
                    case 2:
                        e_1 = _a.sent();
                        console.log(chalk_1.default.red(e_1));
                        return [2 /*return*/, false];
                    case 3: return [2 /*return*/];
                }
            });
        });
    };
    return Link;
}());
exports.default = Link;
