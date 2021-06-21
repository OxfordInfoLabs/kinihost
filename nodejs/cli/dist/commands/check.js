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
var link_1 = __importDefault(require("./link"));
var chalk_1 = __importDefault(require("chalk"));
var container_1 = __importDefault(require("../core/container"));
/**
 * Check task to ensure that we are linked to a site.
 */
var Check = /** @class */ (function () {
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    function Check(link, auth) {
        this._auth = auth ? auth : container_1.default.getInstance("AuthenticationService");
        this._link = link ? link : new link_1.default();
    }
    /**
     * Process method to process check
     */
    Check.prototype.process = function () {
        return __awaiter(this, void 0, void 0, function () {
            var authenticated, linked;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, this._auth.checkAuthenticated()];
                    case 1:
                        authenticated = _a.sent();
                        if (!authenticated) return [3 /*break*/, 3];
                        return [4 /*yield*/, this._link.checkLinked()];
                    case 2:
                        linked = _a.sent();
                        if (linked) {
                            console.log(chalk_1.default.blue("Check:") + chalk_1.default.green(" success"));
                            console.log(chalk_1.default.blue("Site: " + linked.title + " (" + linked.siteKey + ")"));
                            if (linked.lastBuildNumber)
                                console.log(chalk_1.default.blue("Last build: #" + chalk_1.default.bold(linked.lastBuildNumber) + " by " + chalk_1.default.bold(linked.lastBuildUser) + " on " + chalk_1.default.bold(linked.lastBuildTime)));
                            else
                                console.log(chalk_1.default.grey("There have been no builds for this site"));
                        }
                        else {
                            console.log(chalk_1.default.red("\nThis source base is not currently linked to an active site.  Please type " + chalk_1.default.bold("oc static link") + " to link to a site."));
                        }
                        return [2 /*return*/, linked];
                    case 3:
                        console.log(chalk_1.default.red("You are not currently logged in.  Please type " + chalk_1.default.bold("oc login") + " to log in."));
                        return [2 /*return*/, false];
                }
            });
        });
    };
    return Check;
}());
exports.default = Check;
