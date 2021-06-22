import Link from "./link";
import AuthenticationService from "../services/authentication-service";
import chalk from "chalk";
import Container from "../core/container";

/**
 * Check task to ensure that we are linked to a site.
 */
export default class Check {

    private _auth: AuthenticationService;
    private _link: Link;


    /**
     * Constructor mostly for testing
     *
     * @param link
     * @param api
     * @param inquirer
     * @param siteConfig
     */
    constructor(link?: Link, auth?: AuthenticationService) {
        this._auth = auth ? auth : Container.getInstance("AuthenticationService");
        this._link = link ? link : new Link();

    }


    /**
     * Process method to process check
     */
    public async process() {

        let authenticated = await this._auth.checkAuthenticated();

        if (authenticated) {

            let linked = await this._link.checkLinked()

            if (linked) {

                console.log(chalk.blue("Check:") + chalk.green(" success"));
                console.log(chalk.blue("Site: " + linked.title + " (" + linked.siteKey + ")"));

                if (linked.lastBuildNumber)
                    console.log(chalk.blue("Last build: #" + chalk.bold(linked.lastBuildNumber) + " by " + chalk.bold(linked.lastBuildUser) + " on " + chalk.bold(linked.lastBuildTime)));
                else
                    console.log(chalk.grey("There have been no builds for this site"));
            } else {
                console.log(chalk.red("\nThis source base is not currently linked to an active site.  Please use the link command to first link to a site."));
            }

            return linked;


        } else {
            console.log(chalk.red("You are not currently logged in.  Please use the login command to log in."));
            return false;
        }


    }
}
