import Config from "../../src/Core/Config";
import Auth from "../../src/services/authentication-service";
import MockInquirer from "../mock/mock-inquirer";
import Api from "../../src/Core/Api";
import MockApi from "../mock/mock-api";
import getMAC from 'getmac';
import {sha512} from "js-sha512";

describe('Tests for the authentication class', function () {

    let config = new Config("useraccesstoken", "dev", "test");

    let api = new MockApi();


    it('Should be able to log in using valid email / password for none 2FA account', function (done) {

        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);

        api.setCallMethodExpectation("VALIDTOKEN", "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("passwordsam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        let auth = new Auth(config, inquirer, api);

        auth.login().then((result: boolean) => {

            expect(inquirer.promptCalls[0][0].name).toEqual("emailAddress");
            expect(inquirer.promptCalls[0][1].name).toEqual("password");

            expect(result).toBeTruthy();

            expect(config.userToken).toEqual("VALIDTOKEN");

            done();

        });

    });


    it("Should be prompted for a two factor code if 2FA required", function (done) {

        let inquirer = new MockInquirer([
            {"emailAddress": "bob@twofactor.com", "password": "password"},
            {"twoFactorCode": "875867456"}
        ]);

        let auth = new Auth(config, inquirer, api);

        api.setCallMethodExpectation(new Error("bad two factor"), "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "bob@twofactor.com",
            password: sha512("passwordbob@twofactor.com"),
            secondaryToken: getMAC()
        }, "string");


        auth.login().then((result: boolean) => {

            expect(result).toBeFalsy();

            expect(inquirer.promptCalls[0][0].name).toEqual("emailAddress");
            expect(inquirer.promptCalls[0][1].name).toEqual("password");

            expect(inquirer.promptCalls[1][0].name).toEqual("twoFactorCode");

            done();

        });


    });


    it("Should return false if bad username / password", function (done) {

        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "badpass"}
        ]);

        let auth = new Auth(config, inquirer, api);

        auth.logout();

        api.setCallMethodExpectation(new Error("bad username / password"), "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("badpasssam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        auth.login().then((result: boolean) => {

            expect(inquirer.promptCalls[0][0].name).toEqual("emailAddress");
            expect(inquirer.promptCalls[0][1].name).toEqual("password");

            expect(result).toBeFalsy();

            expect(config.userToken.length).toEqual(0);

            done();
        });

    });


    it("Should be able to logout when logged in", (done) => {

        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);

        let auth = new Auth(config, inquirer, api);

        auth.login().then((result: boolean) => {

            expect(result).toBeTruthy();

            expect(config.userToken).toEqual("VALIDTOKEN");


            // Logout
            auth.logout();

            expect(config.userToken).toEqual("");
            done();
        });

    });


    it("Should be able to ensure login for logged out", (done) => {

        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);

        let auth = new Auth(config, inquirer, api);

        // Logout first
        auth.logout();

        auth.ensureAuthenticated().then((result: boolean) => {
            expect(result).toBeTruthy();
            expect(config.userToken).toEqual("VALIDTOKEN");
            done();
        });

    });


    it("Should be able to ensure login for already logged in", (done) => {

        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);

        api.setPingExpectation(true);

        let auth = new Auth(config, inquirer, api);

        // Logout first
        auth.login().then(() => {

            // Confirm that inquirer was not called as already logged in.
            let modifiedAuth = new Auth(config, null, api);

            modifiedAuth.ensureAuthenticated().then((result: boolean) => {
                expect(result).toBeTruthy();
                expect(config.userToken).toEqual("VALIDTOKEN");
                done();
            });

        });


    });

    it("Should not be able to ensure login for bad username / password combi", (done) => {


        let inquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "badpass"}
        ]);

        let auth = new Auth(config, inquirer, api);

        auth.logout();

        auth.ensureAuthenticated().then((result: boolean) => {
            expect(result).toBeFalsy();
            expect(config.userToken.length).toEqual(0);

            done();
        });

    });

});
