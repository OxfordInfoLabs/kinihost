import Link from "./link";
import AuthenticationService from "../services/authentication-service";
/**
 * Check task to ensure that we are linked to a site.
 */
export default class Check {
    private _auth;
    private _link;
    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, auth?: AuthenticationService);
    /**
     * Process method to process check
     */
    process(): Promise<any>;
}
