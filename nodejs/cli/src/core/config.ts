/**
 * Configuration class
 */
import * as os from "os";
import * as fs from "fs";

export default class Config {

    private _mode = "production";
    private _userTokenFilePath = "";
    private _userToken: string = "";

    // Create a new instance of this config object.
    constructor(userTokenFilePath?: string, mode?: string) {
        if (userTokenFilePath)
            this._userTokenFilePath = userTokenFilePath;
        if (mode)
            this._mode = mode;
    }


    get mode(): string {
        return this._mode;
    }

    set mode(value: string) {
        this._mode = value;
    }

    /**
     * Get the user token if set
     */
    get userToken(): string {

        if (!this._userToken) {
            if (!this._userTokenFilePath) {
                this._userTokenFilePath = os.homedir() + "/.kinisite.json" + (this.mode == "production" ? "" : "-" + this.mode);
            }
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

        if (!this._userTokenFilePath) {
            this._userTokenFilePath = os.homedir() + "/.kinisite.json" + (this.mode == "production" ? "" : "-" + this.mode);
        }

        this._userToken = value;
        fs.writeFileSync(this._userTokenFilePath, value);
    }


    get apiEndpoint(): string {
        if (this.mode == "production") {
            return "https://webservices.oxfordcyber.uk"
        } else {
            return "http://backend.oxfordcyber.test:8080";
        }
    }


    // Load the current user token from the file if set.
    private _loadUserToken() {
        if (fs.existsSync(this._userTokenFilePath)) {
            this._userToken = fs.readFileSync(this._userTokenFilePath).toString().trim();
        }
    }
};
