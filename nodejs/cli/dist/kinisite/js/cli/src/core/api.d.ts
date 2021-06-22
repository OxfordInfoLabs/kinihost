import Config from "./config";
/**
 * Authentication functions
 */
export default class Api {
    private _config;
    private static _instance;
    /**
     * Construct with a config object
     *
     * @param config
     * @param inquirer
     */
    constructor(config?: Config);
    static instance(): Api;
    /**
     * Convenient ping method
     */
    ping(): any;
    /**
     * Call a method on the remote web service, using the passed options.
     */
    callMethod(requestPath: string, method?: string, params?: any, payload?: any, returnClass?: any): any;
    private _processReturnValue;
    private _processPayload;
}
