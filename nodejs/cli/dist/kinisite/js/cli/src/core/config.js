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
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Configuration class
 */
var os = __importStar(require("os"));
var fs = __importStar(require("fs"));
var Config = /** @class */ (function () {
    // Create a new instance of this config object.
    function Config(userTokenFilePath, mode) {
        this._mode = "production";
        this._userTokenFilePath = "";
        this._userToken = "";
        if (userTokenFilePath)
            this._userTokenFilePath = userTokenFilePath;
        if (mode)
            this._mode = mode;
    }
    // Get the singleton instance.
    Config.instance = function () {
        if (!Config._instance)
            Config._instance = new Config();
        return Config._instance;
    };
    Object.defineProperty(Config.prototype, "mode", {
        get: function () {
            return this._mode;
        },
        set: function (value) {
            this._mode = value;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Config.prototype, "userToken", {
        /**
         * Get the user token if set
         */
        get: function () {
            if (!this._userToken) {
                if (!this._userTokenFilePath) {
                    this._userTokenFilePath = os.homedir() + "/.kinisite.json" + (this.mode == "production" ? "" : "-" + this.mode);
                }
                this._loadUserToken();
            }
            return this._userToken;
        },
        /**
         * Set the user token to a new value.
         *
         * @param value
         */
        set: function (value) {
            if (!this._userTokenFilePath) {
                this._userTokenFilePath = os.homedir() + "/.kinisite.json" + (this.mode == "production" ? "" : "-" + this.mode);
            }
            this._userToken = value;
            fs.writeFileSync(this._userTokenFilePath, value);
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Config.prototype, "apiEndpoint", {
        get: function () {
            if (this.mode == "production") {
                return "https://webservices.oxfordcyber.uk";
            }
            else {
                return "http://backend.oxfordcyber.test:8080";
            }
        },
        enumerable: false,
        configurable: true
    });
    // Load the current user token from the file if set.
    Config.prototype._loadUserToken = function () {
        if (fs.existsSync(this._userTokenFilePath)) {
            this._userToken = fs.readFileSync(this._userTokenFilePath).toString().trim();
        }
    };
    return Config;
}());
exports.default = Config;
;
