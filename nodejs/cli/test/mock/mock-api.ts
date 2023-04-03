/**
 * Mock API class
 */
const md5 = require('md5');

import Api from "../../src/core/api";

export default class MockApi extends Api {

    /**
     * @var boolean
     * @private
     */
    private _pingResult: boolean = false;


    /**
     * Programmed call results
     *
     * @private
     */
    private _callResults: any = {};

    public setPingExpectation(pingResult: boolean) {
        this._pingResult = pingResult;
    }

    // Set a call method expectation
    public setCallMethodExpectation(result: any, requestPath: string, method: string = "GET", params: any = {}, payload: any = null, returnClass: any = "string") {
        let paramKey = requestPath + method + JSON.stringify(params) + JSON.stringify(payload) + JSON.stringify(returnClass);
        this._callResults[md5(paramKey)] = result;
    }



    /**
     * Return programmed result
     */
    public async ping(): Promise<any> {
        return this._pingResult;
    }

    /**
     * Call a method on the remote web service, using the passed options.
     */
    public async callMethod(requestPath: string, method: string = "GET", params: any = {}, payload: any = null, returnClass: any = "string"): Promise<any> {
        let paramKey = requestPath + method + JSON.stringify(params) + JSON.stringify(payload) + JSON.stringify(returnClass);

        let result = this._callResults[md5(paramKey)];

        if (result instanceof Error) {
            throw result.message;
        } else {
            return result;
        }
    }

};