/**
 * Main kinihost CLI class
 */

import program from "commander";
import chalk from "chalk";

import Container from "./core/container";

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
                    authenticationService.login().then(() => {
                        console.log(chalk.green("\nYou have logged in successfully"));
                    });

                }
            },
            {
                "name": "logout",
                "description": "Logout from the " + cliDisplayName + " system",
                "action": (env: any) => {
                    authenticationService.logout();
                    console.log(chalk.green("\nYou have been logged out"));
                }
            }
        ]

        // Apply all commands
        builtInCommands.concat(additionalCommands).forEach((command: any) => {

            program.command(command.name).description(command.description).action((...params) => {
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