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
var check_1 = __importDefault(require("./commands/check"));
var link_1 = __importDefault(require("./commands/link"));
var status_1 = __importDefault(require("./commands/status"));
var push_1 = __importDefault(require("./commands/push"));
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
                    authenticationService.login();
                }
            },
            {
                "name": "logout",
                "description": "Logout from the " + cliDisplayName + " system",
                "action": function (env) {
                    authenticationService.logout();
                }
            },
            {
                "name": "link",
                "description": "Link the current directory to an existing " + cliDisplayName + " site",
                "action": function (siteKey, env) {
                    new link_1.default().process(env ? siteKey : null);
                }
            },
            {
                "name": "check",
                "description": "Check whether this source is linked to a site and display status info",
                "action": function (env) {
                    new check_1.default().process();
                }
            },
            {
                "name": "status",
                "description": "Check the status of the local source base prior to a push",
                "action": function (env) {
                    new status_1.default().process();
                }
            },
            {
                "name": "push",
                "description": "Push the latest source for the currently linked website",
                "action": function (env) {
                    new push_1.default().process();
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
