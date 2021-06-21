export default class Config {
    private _cliDisplayName;
    private _apiEndpoint;
    private _configFilename;
    private _configDirectory;
    private _userToken;
    constructor(configFilename?: string, apiEndpoint?: string, configDirectory?: string);
    get cliDisplayName(): string;
    set cliDisplayName(value: string);
    get configFilename(): string;
    set configFilename(value: string);
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
    set apiEndpoint(value: string);
    private _loadUserToken;
}
