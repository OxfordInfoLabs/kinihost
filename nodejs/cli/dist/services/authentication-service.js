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
var api_1 = __importDefault(require("../core/api"));
var chalk_1 = __importDefault(require("chalk"));
var js_sha512_1 = require("js-sha512");
var getmac_1 = __importDefault(require("getmac"));
var container_1 = __importDefault(require("../core/container"));
var liveInquirer = require('inquirer');
/**
 * Authentication functions
 */
var AuthenticationService = /** @class */ (function () {
    /**
     * Construct with a config object and inquirer
     *
     * @param config
     * @param inquirer
     */
    function AuthenticationService(config, inquirer, api) {
        this._config = config ? config : container_1.default.getInstance("Config");
        this._api = api ? api : new api_1.default(this._config);
        this._inquirer = inquirer ? inquirer : liveInquirer;
    }
    /**
     * Check authenticated
     */
    AuthenticationService.prototype.checkAuthenticated = function (reportError) {
        return __awaiter(this, void 0, void 0, function () {
            var e_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!this._config.userToken) return [3 /*break*/, 5];
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 3, , 4]);
                        return [4 /*yield*/, this._api.ping()];
                    case 2:
                        _a.sent();
                        return [2 /*return*/, true];
                    case 3:
                        e_1 = _a.sent();
                        return [2 /*return*/, false];
                    case 4: return [3 /*break*/, 6];
                    case 5: return [2 /*return*/, false];
                    case 6: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Ensure authenticated
     */
    AuthenticationService.prototype.ensureAuthenticated = function () {
        return __awaiter(this, void 0, void 0, function () {
            var authenticated;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this.checkAuthenticated(true)];
                    case 1:
                        authenticated = _a.sent();
                        if (!authenticated) return [3 /*break*/, 2];
                        return [2 /*return*/, true];
                    case 2: return [4 /*yield*/, this.login()];
                    case 3: return [2 /*return*/, _a.sent()];
                }
            });
        });
    };
    /**
     * Login handler
     */
    AuthenticationService.prototype.login = function () {
        return __awaiter(this, void 0, void 0, function () {
            var config, macAddress, userAccessToken, error_1, twoFactorConfig, userAccessToken, error_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        console.log("\nPlease login with your " + this._config.cliDisplayName + " email address and password.\n");
                        return [4 /*yield*/, this._inquirer.prompt([
                                {
                                    "type": "input",
                                    "name": "emailAddress",
                                    "message": "Please enter your email address: "
                                }, {
                                    "type": "password",
                                    "name": "password",
                                    "message": "Please enter your password: "
                                }
                            ])];
                    case 1:
                        config = _a.sent();
                        macAddress = (0, getmac_1.default)();
                        config.password = (0, js_sha512_1.sha512)(config.password + config.emailAddress);
                        _a.label = 2;
                    case 2:
                        _a.trys.push([2, 4, , 12]);
                        return [4 /*yield*/, this._api.callMethod("/cli/auth/accessToken", "POST", {}, {
                                emailAddress: config.emailAddress,
                                password: config.password,
                                secondaryToken: macAddress
                            }, "string")];
                    case 3:
                        userAccessToken = _a.sent();
                        this._config.userToken = userAccessToken;
                        console.log(chalk_1.default.green("\nYou have logged in successfully"));
                        return [2 /*return*/, true];
                    case 4:
                        error_1 = _a.sent();
                        if (!(error_1.toString().indexOf("two factor") > 1)) return [3 /*break*/, 10];
                        return [4 /*yield*/, this._inquirer.prompt([
                                {
                                    "type": "input",
                                    "name": "twoFactorCode",
                                    "message": "Please enter the two factor code from your authenticator app"
                                }
                            ])];
                    case 5:
                        twoFactorConfig = _a.sent();
                        _a.label = 6;
                    case 6:
                        _a.trys.push([6, 8, , 9]);
                        return [4 /*yield*/, this._api.callMethod("/cli/auth/accessToken", "POST", {}, {
                                emailAddress: config.emailAddress,
                                password: config.password,
                                twoFactorCode: twoFactorConfig.twoFactorCode,
                                secondaryToken: macAddress
                            }, "string")];
                    case 7:
                        userAccessToken = _a.sent();
                        this._config.userToken = userAccessToken;
                        return [2 /*return*/, true];
                    case 8:
                        error_2 = _a.sent();
                        console.log(chalk_1.default.red(error_2));
                        return [2 /*return*/, false];
                    case 9:
                        ;
                        return [3 /*break*/, 11];
                    case 10:
                        console.log(chalk_1.default.red(error_1));
                        return [2 /*return*/, false];
                    case 11: return [3 /*break*/, 12];
                    case 12: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Logout handler - simply null the user token
     */
    AuthenticationService.prototype.logout = function () {
        this._config.userToken = "";
        console.log(chalk_1.default.green("\nYou have been logged out"));
    };
    return AuthenticationService;
}());
exports.default = AuthenticationService;
