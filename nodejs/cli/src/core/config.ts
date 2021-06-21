/**
 * Configuration class
 */
import * as os from "os";
import * as fs from "fs";

export default class Config {

    private _cliDisplayName = "Test CLI";
    private _apiEndpoint = "http://localhost:8080";
    private _configFilename = "config.json";
    private _configDirectory = os.homedir();
    private _userToken: string = "";

    // Create a new instance of this config object.
    constructor(configFilename?: string, apiEndpoint?: string, configDirectory?: string) {
        if (configFilename)
            this._configFilename = configFilename;
        if (apiEndpoint)
            this._apiEndpoint = apiEndpoint;
        if (configDirectory)
            this._configDirectory = configDirectory;
    }


    get cliDisplayName(): string {
        return this._cliDisplayName;
    }

    set cliDisplayName(value: string) {
        this._cliDisplayName = value;
    }

    get configFilename(): string {
        return this._configFilename;
    }

    set configFilename(value: string) {
        this._configFilename = value;
    }


    /**
     * Get the user token if set
     */
    get userToken(): string {

        if (!this._userToken) {
            this._loadUserToken();
        }

        return this._userToken;
    }

    /**
     * Set the user token to a new value.
     *
     * @param value
     */
    set userToken(value: string) {
        const configFilename = this._configDirectory + "/." + this._configFilename;
        this._userToken = value;
        fs.writeFileSync(configFilename, value);
    }


    get apiEndpoint(): string {
        return this._apiEndpoint;
    }


    set apiEndpoint(value: string) {
        this._apiEndpoint = value;
    }

// Load the current user token from the file if set.
    private _loadUserToken() {
        const configFilename = this._configDirectory + "/." + this._configFilename;
        if (fs.existsSync(configFilename)) {
            this._userToken = fs.readFileSync(configFilename).toString().trim();
        }
    }
};
