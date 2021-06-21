import Config from "../../src/core/config";
import Api from "../../src/core/api";
import MockInquirer from "../mock/mock-inquirer";
import Auth from "../../src/services/authentication-service";
import SiteConfig from "../../src/core/site-config";
import Check from "../../src/commands/check";
import Link from "../../src/commands/link";
import Container from "../../src/core/container";
import MockApi from "../mock/mock-api";
import {sha512} from "js-sha512";
import getMAC from "getmac";


describe('Tests for the check command', function () {

    let config = new Config("useraccesstoken", "dev");
    let link: Link;
    let auth: Auth;
    let siteConfig = Container.getInstance("SiteConfig");
    let api: MockApi;


    beforeEach(() => {

        api = new MockApi();

        let authenticationInquirer = new MockInquirer([
            {"emailAddress": "sam@samdavisdesign.co.uk", "password": "password"}
        ]);
        auth = new Auth(config, authenticationInquirer, api);

        auth.logout();

        siteConfig.siteConfig = {};


        link = new Link(auth, siteConfig, api);

    });


    it('Should return false if not logged in', done => {

        let check = new Check(link, auth);

        check.process().then(result => {
            expect(result).toBeFalsy();
            done();
        });


    });


    it('Should return false if logged in but not linked.', done => {


        api.setCallMethodExpectation("VALIDTOKEN", "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("passwordsam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        auth.login().then(() => {
            let check = new Check(link, auth);

            check.process().then(result => {
                expect(result).toBeFalsy();
                done();
            });
        });


    });


    it('Should return true if logged in and linked.', done => {

        api.setCallMethodExpectation("VALIDTOKEN", "/cli/auth/accessToken", "POST", {}, {
            emailAddress: "sam@samdavisdesign.co.uk",
            password: sha512("passwordsam@samdavisdesign.co.uk"),
            secondaryToken: getMAC()
        }, "string");


        api.setCallMethodExpectation({"siteKey": "lucientaylor", "title": "Lucien Taylor"}, "/cli/site/lucientaylor",
            "GET", {}, null, "string");


        auth.login().then(() => {

            link.process("lucientaylor").then(() => {

                let check = new Check(link, auth);
                check.process().then(result => {
                    expect(result).toBeTruthy();
                    done();
                });

            });

        });

    });


});
