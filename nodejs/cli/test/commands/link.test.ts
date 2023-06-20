import Config from "../../src/core/config";
import Auth from "../../src/services/authentication-service";
import Link from "../../src/commands/link";
import MockInquirer from "../mock/mock-inquirer";
import Api from "../../src/core/api";
import * as fs from "fs";
import SiteConfig from "../../src/core/site-config";
import Container from "../../src/core/container";
import MockApi from "../mock/mock-api";
import {sha512} from "js-sha512";
import getMAC from "getmac";

describe('Tests for the link command', function () {

    let config = new Config("useraccesstoken", "http://localhost:3000", "test");
    let api = new MockApi(config);
    let auth: Auth;
    let siteConfig: SiteConfig = Container.getInstance("SiteConfig");

    beforeEach(() => {

        let authenticationInquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);
        auth = new Auth(config, authenticationInquirer, api);

        auth.logout();

        siteConfig.siteConfig = {};


    });


    it('Should return false if not logged in and bad username and password entered.', function (done) {


        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "badpass"}
        ]);

        api.setCallMethodExpectation(new Error("bad username / password"), "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("badpasssam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        let link = new Link(new Auth(config, inquirer, api), siteConfig, api);

        link.process().then(result => {
            expect(result).toBeFalsy();
            done();
        });

    });


    it('Should return false if logged in but no sites exist for the user yet.', function (done) {

        let authenticationInquirer = new MockInquirer([
            {"emailAddress": "john@shoppingonline.com", "password": "password"}
        ]);
        auth = new Auth(config, authenticationInquirer, api);

        let link = new Link(auth, siteConfig, api, authenticationInquirer);


        api.setCallMethodExpectation("VALIDTOKEN", "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("passwordsam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        link.process().then(result => {

            expect(result).toBeFalsy();

            done();

        });


    });


    it('Should return true if logged in and valid choice chosen from list of available sites to link and kinisite.json file is written', function (done) {


        // Remove a kinisite config file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }

        let inquirer = new MockInquirer([
            {"siteKey": "lucientaylor"}
        ]);


        api.setCallMethodExpectation([
            {"siteKey": "lucientaylor", "title": "Lucien Taylor"},
            {"siteKey": "nathanalan", "title": "Nathan Alan"},
            {"siteKey": "samdavisdotcom", "title": "Sam Davis Design .COM"},
            {"siteKey": "woollenmill", "title": "Woollen Mill Site"}
        ], "/cli/site", "GET");


        let link = new Link(auth, siteConfig, api, inquirer);

        link.process().then(result => {

            expect(result).toBeTruthy();

            expect(inquirer.promptCalls[0][0].name).toEqual("siteKey");
            expect(inquirer.promptCalls[0][0].choices).toEqual([
                {
                    name: "Lucien Taylor",
                    value: "lucientaylor"
                },
                {
                    name: "Nathan Alan",
                    value: "nathanalan"
                },
                {
                    name: "Sam Davis Design .COM",
                    value: "samdavisdotcom"
                },
                {
                    name: "Woollen Mill Site",
                    value: "woollenmill"
                }
            ]);

            expect(fs.existsSync("config.json")).toBeTruthy();
            expect(JSON.parse(fs.readFileSync("config.json").toString()).deployment).toEqual({
                siteKey: 'lucientaylor'
            });

            done();

        });


    });


    it('Should return true if logged in and explicit site key passed to the link object, and kinisite.json file created', function (done) {


        // Remove a kinisite.json file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }

        let inquirer = new MockInquirer([
            {"option": 1}
        ]);

        let link = new Link(auth, siteConfig, api, inquirer);

        link.process("lucientaylor").then(result => {

            expect(result).toBeTruthy();

            expect(inquirer.promptCalls[0]).toBeUndefined();

            expect(fs.existsSync("config.json")).toBeTruthy();
            expect(JSON.parse(fs.readFileSync("config.json").toString()).deployment.siteKey).toEqual("lucientaylor");

            done();

        });


    });


    it("Should return false if bad site key passed to the link object and no kinisite.json file created", function (done) {


        // Remove a config.json file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }

        let link = new Link(auth, siteConfig, api, null);


        // Programme failure
        api.setCallMethodExpectation(new Error("Bad site key"), "/cli/site/badsitekey", "GET");


        link.process("badsitekey").then(result => {

            expect(result).toBeFalsy();

            expect(fs.existsSync("config.json")).toBeFalsy();

            done();

        });

    });


    it("Should return false if non accessible site key passed to the link object and no config.json file created", function (done) {


        // Remove a config.json file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }

        let link = new Link(auth, siteConfig, api, null);

        // Programme failure
        api.setCallMethodExpectation(new Error("None accessible"), "/cli/site/paperchase", "GET");


        link.process("paperchase").then(result => {

            expect(result).toBeFalsy();

            expect(fs.existsSync("config.json")).toBeFalsy();

            done();

        });

    });


    it("Ensure linked should fail if bad auth supplied.", function (done) {

        // Remove a config.json file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }


        let authenticationInquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "dodgy"}
        ]);
        auth = new Auth(config, authenticationInquirer);


        // Not logged in
        let link = new Link(auth, siteConfig, api, null);

        // link.ensureLinked().then(result => {
        //     expect(result).toBeFalsy();
        //     done();
        // });

        done();

    });


    it("Ensure linked should fail if bad site key selected", function (done) {

        // Remove a config.json file if it exists
        if (fs.existsSync("config.json")) {
            fs.unlinkSync("config.json");
        }

        let inquirer = new MockInquirer([
            {"option": 50}
        ]);

        // Not logged in
        let link = new Link(auth, siteConfig, api, inquirer);

        // Programme failure
        api.setCallMethodExpectation(new Error("Bad key"), "/cli/site/undefined", "GET");


        link.ensureLinked().then(result => {
            expect(result).toBeFalsy();
            done();
        });

    });

    it("Ensure linked should prompt to choose new site if existing site key not valid", function (done) {

        siteConfig.siteConfig = {"siteKey": "badboy", "config": {"publishDirectory": null}};

        let inquirer = new MockInquirer([
            {"siteKey": "lucientaylor"}
        ]);

        // Not logged in
        let link = new Link(auth, siteConfig, api, inquirer);

        link.ensureLinked().then(result => {
            expect(result).toBeTruthy();

            expect(inquirer.promptCalls[0][0].name).toEqual("siteKey");
            expect(inquirer.promptCalls[0][0].choices).toEqual([
                {
                    name: "Lucien Taylor",
                    value: "lucientaylor"
                },
                {
                    name: "Nathan Alan",
                    value: "nathanalan"
                },
                {
                    name: "Sam Davis Design .COM",
                    value: "samdavisdotcom"
                },
                {
                    name: "Woollen Mill Site",
                    value: "woollenmill"
                }
            ]);

            done();
        });

    });


    it("Ensure linked should prompt to choose new site if no existing site key", function (done) {


        let inquirer = new MockInquirer([
            {"siteKey": "lucientaylor"}
        ]);

        // Not logged in
        let link = new Link(auth, siteConfig, api, inquirer);

        link.ensureLinked().then(result => {
            expect(result).toBeTruthy();

            expect(inquirer.promptCalls[0][0].name).toEqual("siteKey");
            expect(inquirer.promptCalls[0][0].choices).toEqual([
                {
                    name: "Lucien Taylor",
                    value: "lucientaylor"
                },
                {
                    name: "Nathan Alan",
                    value: "nathanalan"
                },
                {
                    name: "Sam Davis Design .COM",
                    value: "samdavisdotcom"
                },
                {
                    name: "Woollen Mill Site",
                    value: "woollenmill"
                }
            ]);

            done();
        });

    });

});
