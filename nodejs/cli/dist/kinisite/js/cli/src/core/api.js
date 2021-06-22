"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
var config_1 = __importDefault(require("./config"));
var asyncRequest = require('then-request');
var getmac_1 = __importDefault(require("getmac"));
/**
 * Authentication functions
 */
var Api = /** @class */ (function () {
    /**
     * Construct with a config object
     *
     * @param config
     * @param inquirer
     */
    function Api(config) {
        this._config = config ? config : config_1.default.instance();
    }
    // Get the singleton instance.
    Api.instance = function () {
        if (!Api._instance)
            Api._instance = new Api();
        return Api._instance;
    };
    /**
     * Convenient ping method
     */
    Api.prototype.ping = function () {
        return this.callMethod("/cli/util/ping");
    };
    /**
     * Call a method on the remote web service, using the passed options.
     */
    Api.prototype.callMethod = function (requestPath, method, params, payload, returnClass) {
        var _this = this;
        if (method === void 0) { method = "GET"; }
        if (params === void 0) { params = {}; }
        if (payload === void 0) { payload = null; }
        if (returnClass === void 0) { returnClass = "string"; }
        return new Promise(function (resolve, reject) {
            var url = _this._config.apiEndpoint + "/" + requestPath;
            var macAddress = getmac_1.default();
            var authParams = {
                "userAccessToken": _this._config.userToken,
                "secondaryToken": macAddress
            };
            var getParams = Object.assign({}, authParams);
            // Also assign any params to the object.
            getParams = Object.assign(getParams, params);
            var paramsAsStrings = [];
            Object.keys(getParams).forEach(function (key) {
                if (getParams[key] !== undefined)
                    paramsAsStrings.push(key + "=" + getParams[key]);
            });
            if (paramsAsStrings.length > 0)
                url += "?" + paramsAsStrings.join("&");
            // If we have a payload, ensure we remap _ properties back in object modes
            if (payload) {
                payload = _this._processPayload(payload);
            }
            asyncRequest(method, url, payload ? {
                json: payload
            } : null).done(function (res) {
                var rawBody = res.body.toString();
                var body = rawBody ? JSON.parse(res.body.toString()) : { message: null };
                if (res.statusCode != 200) {
                    var errors_1 = [];
                    if (body.validationErrors) {
                        var validationErrors = Object.values(body.validationErrors);
                        validationErrors.forEach(function (error) {
                            if (typeof error == 'object') {
                                var subErrors = Object.values(error);
                                subErrors.forEach(function (subError) {
                                    errors_1.push(subError.errorMessage);
                                });
                            }
                            else {
                                errors_1.push(error);
                            }
                        });
                    }
                    else {
                        errors_1.push(body.message);
                    }
                    reject(errors_1.join("\n"));
                }
                else {
                    resolve(_this._processReturnValue(body, returnClass));
                }
            });
        });
    };
    // Process a return value and ensure we get the correct class.
    Api.prototype._processReturnValue = function (returnValue, returnValueClass) {
        var _this = this;
        // If we are primitive, quit
        if (typeof returnValueClass == "string") {
            return returnValue;
        }
        else {
            if (Array.isArray(returnValue)) {
                var newArray_1 = [];
                returnValue.forEach(function (entry) {
                    newArray_1.push(_this._processReturnValue(entry, returnValueClass));
                });
                return newArray_1;
            }
            else {
                var newObject = new returnValueClass();
                newObject.__setData(returnValue);
                return newObject;
            }
        }
    };
    // Process the payload getting data
    Api.prototype._processPayload = function (payload) {
        var _this = this;
        if (Array.isArray(payload)) {
            var newPayload_1 = [];
            payload.forEach(function (entry) {
                newPayload_1.push(_this._processPayload(entry));
            });
            return newPayload_1;
        }
        else if (payload === Object(payload)) {
            var newPayload_2 = {};
            Object.keys(payload).forEach(function (key) {
                var value = payload[key];
                if (key.substr(0, 1) == "_") {
                    key = key.substr(1);
                }
                newPayload_2[key] = value;
            });
            return newPayload_2;
        }
        else {
            return payload;
        }
    };
    return Api;
}());
exports.default = Api;
