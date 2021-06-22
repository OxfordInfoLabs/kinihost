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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Create site handling class.
 */
var fs = __importStar(require("fs"));
var chalk_1 = __importDefault(require("chalk"));
var api_1 = __importDefault(require("../../core/api"));
var config_1 = __importDefault(require("../../core/config"));
var site_config_schema_1 = __importDefault(require("./schema/site-config-schema"));
var jsonschema_1 = require("jsonschema");
var SiteConfig = /** @class */ (function () {
    /**
     * Load the site config
     */
    function SiteConfig(api, contentRoot) {
        if (contentRoot === void 0) { contentRoot = "."; }
        // Loaded site config
        this._siteConfig = {};
        this._configFilename = "";
        this._api = api ? api : api_1.default.instance();
        this._contentRoot = contentRoot;
        this._loadSiteConfig();
    }
    // Get the singleton instance.
    SiteConfig.instance = function () {
        if (!SiteConfig._instance)
            SiteConfig._instance = new SiteConfig();
        return SiteConfig._instance;
    };
    Object.defineProperty(SiteConfig.prototype, "configFilename", {
        /**
         * Get the config filename
         */
        get: function () {
            if (this._configFilename) {
                return this._configFilename;
            }
            else
                return "kinisite" + (config_1.default.instance().mode == "production" ? "" : "-" + config_1.default.instance().mode) + ".json";
        },
        /**
         * Set the config filename
         *
         * @param configFilename
         */
        set: function (configFilename) {
            this._configFilename = configFilename;
            this._loadSiteConfig();
        },
        enumerable: false,
        configurable: true
    });
    /**
     * Confirm whether a site is linked.
     */
    SiteConfig.prototype.isSiteLinked = function () {
        return this.deploymentConfig.siteKey;
    };
    Object.defineProperty(SiteConfig.prototype, "contentRoot", {
        /**
         * Get content root.
         */
        get: function () {
            return this._contentRoot;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SiteConfig.prototype, "siteKey", {
        /**
         * Get the site key for the current site.
         */
        get: function () {
            return this.deploymentConfig.siteKey ? this.deploymentConfig.siteKey : null;
        },
        /**
         * Update the site key following a successful link
         *
         * @param siteKey
         */
        set: function (siteKey) {
            this.deploymentConfig.siteKey = siteKey;
            this.siteConfig = this._siteConfig;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SiteConfig.prototype, "localServerConfig", {
        /**
         * Get local server config if defined.
         */
        get: function () {
            return this._siteConfig.localServer ? this._siteConfig.localServer : {};
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SiteConfig.prototype, "deploymentConfig", {
        /**
         * Get the main config
         */
        get: function () {
            if (!this._siteConfig.deployment) {
                this._siteConfig.deployment = {};
            }
            return this._siteConfig.deployment;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(SiteConfig.prototype, "publishDirectory", {
        /**
         * Get the publish directory
         */
        get: function () {
            return this.deploymentConfig && this.deploymentConfig.publishDirectory ? this.deploymentConfig.publishDirectory : null;
        },
        enumerable: false,
        configurable: true
    });
    /**
     * Set the publish directory (also update the server side)
     *
     * @param publishDirectory
     */
    SiteConfig.prototype.updatePublishDirectory = function (publishDirectory) {
        var _this = this;
        return new Promise(function (resolve) {
            // If the file exists, continue
            if (fs.existsSync(_this._contentRoot + "/" + publishDirectory)) {
                // Update the local copy once server is synced
                _this.deploymentConfig.publishDirectory = publishDirectory;
                _this.siteConfig = _this._siteConfig;
                resolve(true);
            }
            else {
                console.log(chalk_1.default.red("\nThe specified publish directory does not exist under the current directory"));
                resolve(false);
            }
        });
    };
    // Load the site config
    SiteConfig.prototype._loadSiteConfig = function () {
        this._siteConfig = {};
        if (fs.existsSync(this.configFilename)) {
            var validated = void 0;
            try {
                this._siteConfig = JSON.parse(fs.readFileSync(this.configFilename).toString());
                var validator = new jsonschema_1.Validator();
                validated = validator.validate(this._siteConfig, site_config_schema_1.default);
            }
            catch (e) {
                validated = {
                    errors: [
                        {
                            stack: "instance.Malformed JSON file found"
                        }
                    ]
                };
            }
            if (validated.errors.length > 0) {
                console.log(chalk_1.default.red("\nThe site config file " + this.configFilename + " has the following validation errors:"));
                validated.errors.forEach((function (error) {
                    console.log(chalk_1.default.yellow(error.stack.substr(9)));
                }));
                process.exit(0);
            }
        }
    };
    Object.defineProperty(SiteConfig.prototype, "siteConfig", {
        set: function (value) {
            this._siteConfig = value;
            fs.writeFileSync(this.configFilename, JSON.stringify(value));
        },
        enumerable: false,
        configurable: true
    });
    return SiteConfig;
}());
exports.default = SiteConfig;
