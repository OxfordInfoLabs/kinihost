/**
 * Main kinihost CLI class
 */

const {program} = require("commander");
import chalk from "chalk";

import Container from "./core/container";
import Check from "./commands/check";
import Link from "./commands/link";
import Status from "./commands/status";
import Push from "./commands/push";
import Download from "./commands/download";

export default class KinihostCli {

    // Handled property
    private _handled = false;

    constructor(apiEndpoint: string, defaultConfigFilename: string, cliDisplayName: string,
                additionalCommands: any[] = []) {

        // Initialise the config object with the passed values
        const config = Container.getInstance("Config");
        config.cliDisplayName = cliDisplayName;
        config.configFilename = defaultConfigFilename;
        config.apiEndpoint = apiEndpoint;

        // Initialise
        this.initialise(cliDisplayName, defaultConfigFilename, additionalCommands);

    }


    // Initialise the cli
    private initialise(cliDisplayName: string, defaultConfigFilename: string, additionalCommands: any[]) {

        // Define version and description for the Site Atomic CLI
        program
            .version('0.0.1')
            .description(cliDisplayName + " CLI");

        const authenticationService = Container.getInstance("AuthenticationService");

        let builtInCommands = [
            {
                "name": "login",
                "description": "Login to the " + cliDisplayName + " system",
                "action": (env: any) => {
                    authenticationService.login();
                }
            },
            {
                "name": "logout",
                "description": "Logout from the " + cliDisplayName + " system",
                "action": (env: any) => {
                    authenticationService.logout();
                }
            },
            {
                "name": "link",
                "description": "Link the current directory to an existing " + cliDisplayName + " site",
                "action": (siteKey: any, env: any) => {
                    new Link().process(env ? siteKey : null);
                }
            },
            {
                "name": "check",
                "description": "Check whether this source is linked to a site and display status info",
                "action": (env: any) => {
                    new Check().process();
                }
            },
            {
                "name": "status",
                "description": "Check the status of the local source base prior to a push",
                "action": (env: any) => {
                    new Status().process();
                }
            },
            {
                "name": "push",
                "description": "Push the latest source for the currently linked website",
                "action": (env: any) => {
                    new Push().process();
                }
            },
            {
                "name": "download",
                "description": "Download the latest source for the currently linked website",
                "action": (env: any) => {
                    new Download().process();
                }
            }
        ]

        // Apply all commands
        builtInCommands.concat(additionalCommands).forEach((command: any) => {

            program.command(command.name).description(command.description).action((...params:any[]) => {
                this._handled = true;
                command.action.apply(this, params);
            });

        });


        program.option('-c, --siteconfig <path>', 'Alternative path to a config file to use for site configuration (defaults to ' + defaultConfigFilename + ')');


        // @ts-ignore
        program.parse(process.argv);


        if (!this._handled) {
            // @ts-ignore
            console.log(chalk.red("Error: Unknown command %s supplied."), process.argv[2]);
        }


    }


}