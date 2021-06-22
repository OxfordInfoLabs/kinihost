"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Container class
 */
var site_config_1 = __importDefault(require("./site-config"));
var api_1 = __importDefault(require("./api"));
var config_1 = __importDefault(require("./config"));
var authentication_service_1 = __importDefault(require("../services/authentication-service"));
var Container = /** @class */ (function () {
    function Container() {
    }
    /**
     * Get an instance by class name
     *
     * @param className
     */
    Container.getInstance = function (className) {
        this.initialiseInstanceClasses();
        if (!this._instances[className]) {
            this._instances[className] = Reflect.construct(this._instanceClasses[className], []);
        }
        return this._instances[className];
    };
    /**
     * Set an instance class
     *
     * @param baseClassName
     * @param newInstanceClassName
     */
    Container.setInstanceClass = function (baseClassName, newInstanceClassName) {
        this.initialiseInstanceClasses();
        this._instanceClasses[baseClassName] = newInstanceClassName;
    };
    Container.initialiseInstanceClasses = function () {
        if (!this._instanceClasses) {
            this._instanceClasses = {
                "Api": api_1.default,
                "Config": config_1.default,
                "SiteConfig": site_config_1.default,
                "AuthenticationService": authentication_service_1.default
            };
        }
        return this._instanceClasses;
    };
    /**
     * Repository of instances indexed ny class type
     *
     * @private
     */
    Container._instances = {};
    // Instance classes
    Container._instanceClasses = null;
    return Container;
}());
exports.default = Container;
