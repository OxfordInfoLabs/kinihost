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
    function Config(configFilename, apiEndpoint, configDirectory) {
        this._cliDisplayName = "Test CLI";
        this._apiEndpoint = "http://localhost:8080";
        this._configFilename = "config.json";
        this._configDirectory = os.homedir();
        this._userToken = "";
        if (configFilename)
            this._configFilename = configFilename;
        if (apiEndpoint)
            this._apiEndpoint = apiEndpoint;
        if (configDirectory)
            this._configDirectory = configDirectory;
    }
    Object.defineProperty(Config.prototype, "cliDisplayName", {
        get: function () {
            return this._cliDisplayName;
        },
        set: function (value) {
            this._cliDisplayName = value;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Config.prototype, "configFilename", {
        get: function () {
            return this._configFilename;
        },
        set: function (value) {
            this._configFilename = value;
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
            var configFilename = this._configDirectory + "/." + this._configFilename;
            this._userToken = value;
            fs.writeFileSync(configFilename, value);
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Config.prototype, "apiEndpoint", {
        get: function () {
            return this._apiEndpoint;
        },
        set: function (value) {
            this._apiEndpoint = value;
        },
        enumerable: false,
        configurable: true
    });
    // Load the current user token from the file if set.
    Config.prototype._loadUserToken = function () {
        var configFilename = this._configDirectory + "/." + this._configFilename;
        if (fs.existsSync(configFilename)) {
            this._userToken = fs.readFileSync(configFilename).toString().trim();
        }
    };
    return Config;
}());
exports.default = Config;
;
