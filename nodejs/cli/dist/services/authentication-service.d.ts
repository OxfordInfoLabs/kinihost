import Config from "../core/config";
/**
 * Authentication functions
 */
export default class AuthenticationService {
    private _config;
    private _api;
    private _inquirer;
    /**
     * Construct with a config object and inquirer
     *
     * @param config
     * @param inquirer
     */
    constructor(config?: Config, inquirer?: any, api?: any);
    /**
     * Check authenticated
     */
    checkAuthenticated(reportError?: boolean): Promise<boolean>;
    /**
     * Ensure authenticated
     */
    ensureAuthenticated(): Promise<boolean>;
    /**
     * Login handler
     */
    login(): Promise<boolean>;
    /**
     * Logout handler - simply null the user token
     */
    logout(): void;
}
