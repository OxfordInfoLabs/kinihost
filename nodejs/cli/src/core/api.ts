import Config from "./config";

const asyncRequest = require('then-request');
import getMAC from 'getmac';
import Container from "./container";

/**
 * Authentication functions
 */
export default class Api {

    private _config: Config;

    /**
     * Construct with a config object
     *
     * @param config
     * @param inquirer
     */
    constructor(config?: Config) {
        this._config = config ? config : Container.getInstance("Config");
    }


    /**
     * Convenient ping method
     */
    public ping() {
        return this.callMethod("/cli/util/ping");
    }

    /**
     * Call a method on the remote web service, using the passed options.
     */
    public callMethod(requestPath: string, method: string = "GET", params: any = {}, payload: any = null, returnClass: any = "string"): any {

        return new Promise((resolve, reject) => {

            let url = this._config.apiEndpoint + "/" + requestPath;

            let macAddress: string = getMAC();


            let authParams = {
                "userAccessToken": this._config.userToken,
                "secondaryToken": macAddress
            };

            let getParams: any = Object.assign({}, authParams);

            // Also assign any params to the object.
            getParams = Object.assign(getParams, params);

            let paramsAsStrings: string[] = [];
            Object.keys(getParams).forEach(function (key) {
                if (getParams[key] !== undefined)
                    paramsAsStrings.push(key + "=" + getParams[key]);
            });

            if (paramsAsStrings.length > 0)
                url += "?" + paramsAsStrings.join("&");

            // If we have a payload, ensure we remap _ properties back in object modes
            if (payload) {
                payload = this._processPayload(payload);
            }

             asyncRequest(method, url, payload ? {
                json: payload
            } : null).done((res: any) => {

                var rawBody = res.body.toString();
                var body = rawBody ? JSON.parse(res.body.toString()) : {message: null};

                if (res.statusCode != 200) {
                    let errors: any[] = [];
                    if (body.validationErrors) {
                        let validationErrors = Object.values(body.validationErrors);
                        validationErrors.forEach((error: any) => {
                            if (typeof error == 'object') {
                                let subErrors = Object.values(error);
                                subErrors.forEach((subError: any) => {
                                    errors.push(<any>(subError.errorMessage));
                                });
                            } else {
                                errors.push(error);
                            }
                        });
                    } else {
                        errors.push(body.message);
                    }

                    reject(errors.join("\n"));
                } else {
                    resolve(this._processReturnValue(body, returnClass));
                }
            });

        });

        
    }


    // Process a return value and ensure we get the correct class.
    private _processReturnValue(returnValue: any, returnValueClass: any) {

        // If we are primitive, quit
        if (typeof returnValueClass == "string") {
            return returnValue;
        } else {

            if (Array.isArray(returnValue)) {

                let newArray: any[] = [];
                returnValue.forEach((entry) => {
                    newArray.push(this._processReturnValue(entry, returnValueClass));
                });

                return newArray;

            } else {

                var newObject = new returnValueClass();
                newObject.__setData(returnValue);
                return newObject;

            }

        }


    }


    // Process the payload getting data
    private _processPayload(payload: any): any {

        if (Array.isArray(payload)) {
            let newPayload: any[] = [];
            payload.forEach(entry => {
                newPayload.push(this._processPayload(entry));
            });
            return newPayload;
        } else if (payload === Object(payload)) {
            let newPayload: any = {};
            Object.keys(payload).forEach(key => {
                let value = payload[key];
                if (key.substr(0, 1) == "_") {
                    key = key.substr(1);
                }
                newPayload[key] = value;
            });

            return newPayload;

        } else {
            return payload;
        }
    }


}
