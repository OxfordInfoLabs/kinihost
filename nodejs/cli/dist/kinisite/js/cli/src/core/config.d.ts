export default class Config {
    private _mode;
    private _userTokenFilePath;
    private _userToken;
    private static _instance;
    constructor(userTokenFilePath?: string, mode?: string);
    static instance(): Config;
    get mode(): string;
    set mode(value: string);
    /**
     * Get the user token if set
     */
    get userToken(): string;
    /**
     * Set the user token to a new value.
     *
     * @param value
     */
    set userToken(value: string);
    get apiEndpoint(): string;
    private _loadUserToken;
}
