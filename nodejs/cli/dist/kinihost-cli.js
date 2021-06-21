"use strict";
/**
 * Main kinihost CLI class
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
var commander_1 = __importDefault(require("commander"));
var chalk_1 = __importDefault(require("chalk"));
var container_1 = __importDefault(require("./core/container"));
var KinihostCli = /** @class */ (function () {
    function KinihostCli(apiEndpoint, defaultConfigFilename, cliDisplayName, additionalCommands) {
        if (additionalCommands === void 0) { additionalCommands = []; }
        // Handled property
        this._handled = false;
        // Initialise the config object with the passed values
        var config = container_1.default.getInstance("Config");
        config.cliDisplayName = cliDisplayName;
        config.configFilename = defaultConfigFilename;
        config.apiEndpoint = apiEndpoint;
        // Initialise
        this.initialise(cliDisplayName, defaultConfigFilename, additionalCommands);
    }
    // Initialise the cli
    KinihostCli.prototype.initialise = function (cliDisplayName, defaultConfigFilename, additionalCommands) {
        var _this = this;
        // Define version and description for the Site Atomic CLI
        commander_1.default
            .version('0.0.1')
            .description(cliDisplayName + " CLI");
        var authenticationService = container_1.default.getInstance("AuthenticationService");
        var builtInCommands = [
            {
                "name": "login",
                "description": "Login to the " + cliDisplayName + " system",
                "action": function (env) {
                    authenticationService.login().then(function () {
                        console.log(chalk_1.default.green("\nYou have logged in successfully"));
                    });
                }
            },
            {
                "name": "logout",
                "description": "Logout from the " + cliDisplayName + " system",
                "action": function (env) {
                    authenticationService.logout();
                    console.log(chalk_1.default.green("\nYou have been logged out"));
                }
            }
        ];
        // Apply all commands
        builtInCommands.concat(additionalCommands).forEach(function (command) {
            commander_1.default.command(command.name).description(command.description).action(function () {
                var params = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    params[_i] = arguments[_i];
                }
                _this._handled = true;
                command.action.apply(_this, params);
            });
        });
        commander_1.default.option('-c, --siteconfig <path>', 'Alternative path to a config file to use for site configuration (defaults to ' + defaultConfigFilename + ')');
        // @ts-ignore
        commander_1.default.parse(process.argv);
        if (!this._handled) {
            // @ts-ignore
            console.log(chalk_1.default.red("Error: Unknown command %s supplied."), process.argv[2]);
        }
    };
    return KinihostCli;
}());
exports.default = KinihostCli;
