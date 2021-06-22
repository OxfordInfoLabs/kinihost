import ChangedObject from "../../src/objects/changed-object";
import Auth from "../../src/services/authentication-service";
import SourceUploadBuild from "../../src/objects/source-upload-build";
import SiteConfig from "../../src/core/site-config";
import Link from "../../src/commands/link";
import MockInquirer from "../mock/mock-inquirer";
import Config from "../../src/core/config";
import SourceService from "../../src/services/source-service";
import MockApi from "../mock/mock-api";

const fsExtra = require('fs-extra');
const fs = require('fs');
const rimraf = require("rimraf");
const md5 = require('md5');

/**
 * Tests for the source manager
 */
describe('Tests for the source manager service.', function () {

    let firstTime = true;
    let api: MockApi;
    let auth: Auth;
    let link: Link;
    let siteConfig: SiteConfig;


    beforeEach(() => {


        let config = new Config("useraccesstoken", "http://localhost");
        api = new MockApi(config);

        let authenticationInquirer = new MockInquirer([
            {"emailAddress": "mary@shoppingonline.com", "password": "password"}
        ]);
        auth = new Auth(config, authenticationInquirer);

        siteConfig = new SiteConfig(api, config, "test/test-content/working");


        link = new Link(auth, siteConfig, api, null);


        // Up the timeout for source upload tests.
        jasmine.DEFAULT_TIMEOUT_INTERVAL = 25000;

    });


    it('Can download footprints for site with pre-existing source.', function (done) {

        let sourceService = new SourceService(api);

        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                sourceService.getRemoteObjectFootprints().then((footprints: any) => {

                    expect(footprints["drama.txt"]).toEqual("cd672af2314970abf37b948f5b3af622");
                    expect(footprints["english.txt"]).toEqual("90dc092b96b4e5f8d5f1c8a204c4530d");

                    done();
                });

            });

        });

    });


    it('Can generate local footprints for all files within content directory recursively and following links.', function () {

        // Populate the working directory
        rimraf.sync("test/test-content/working");
        fsExtra.copySync("test/test-content/source", "test/test-content/working");
        fs.symlinkSync("../linked", "test/test-content/working/linked");
        fs.symlinkSync("../css/static.css", "test/test-content/working/linkedfile.css");


        let sourceService = new SourceService(api, siteConfig);

        let localFootprints = sourceService.getLocalObjectFootprints();

        // Expect 9 entries.
        expect(Object.keys(localFootprints).length).toEqual(9);
        expect(localFootprints["test.html"]).toEqual(md5(fs.readFileSync("test/test-content/working/test.html").toString()));
        expect(localFootprints["test2.html"]).toEqual(md5(fs.readFileSync("test/test-content/working/test2.html").toString()));
        expect(localFootprints["sub/testsub.html"]).toEqual(md5(fs.readFileSync("test/test-content/working/sub/testsub.html").toString()));
        expect(localFootprints["sub/nested/testnested.html"]).toEqual(md5(fs.readFileSync("test/test-content/working/sub/nested/testnested.html").toString()));
        expect(localFootprints["linked/test-linked.html"]).toEqual(md5(fs.readFileSync("test/test-content/linked/test-linked.html").toString()));
        expect(localFootprints["linked/sub/test-sub-linked.html"]).toEqual(md5(fs.readFileSync("test/test-content/linked/sub/test-sub-linked.html").toString()));
        expect(localFootprints["linkedfile.css"]).toEqual(md5(fs.readFileSync("test/test-content/css/static.css").toString()));


    });


    it('Can generate changes from supplied remote and local footprints', function () {

        const remoteFootprints = {
            "test1.html": "AAABBB",
            "test2.html": "BBBCCC",
            "test4.html": "ZZZYYY",
            "sub/test3.html": "EEEFFF",
            "sub/nested/test4.html": "GGGHHH"
        };


        const localFootprints = {
            "test3.html": "EEEFFF",
            "test1.html": "AAABBB",
            "test2.html": "IIIJJJ",
            "sub/test4.html": "KKKLLL",
            "sub/nested/test4.html": "GGGHHH"
        }

        let sourceService = new SourceService(api, siteConfig);

        let changes: ChangedObject[] = sourceService.generateChanges(remoteFootprints, localFootprints);
        expect(changes.length).toEqual(5);

        expect(changes).toContain(new ChangedObject("test2.html", "UPDATE", "IIIJJJ"));
        expect(changes).toContain(new ChangedObject("test3.html", "UPDATE", "EEEFFF"));
        expect(changes).toContain(new ChangedObject("test4.html", "DELETE", ""));
        expect(changes).toContain(new ChangedObject("sub/test3.html", "DELETE", ""));
        expect(changes).toContain(new ChangedObject("sub/test4.html", "UPDATE", "KKKLLL"));


    });


    it('Can create remote upload build from supplied changes', function (done) {

        let sourceService = new SourceService(api);

        let changes: ChangedObject[] = [
            new ChangedObject("test1.html", "UPDATE", "EEEFFF"),
            new ChangedObject("my/sub/test2.html", "UPDATE", "GGGHHH"),
            new ChangedObject("test5.html", "DELETE", "AAABBB"),

        ];


        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                /**
                 * Create the remote upload build.
                 */
                sourceService.createRemoteUploadBuild(changes).then((uploadBuild: SourceUploadBuild) => {

                    expect(uploadBuild.buildId !== null).toBeTruthy();
                    expect(Object.keys(uploadBuild.uploadUrls).length).toEqual(2);

                    done();

                });
            });
        });

    });


    it('Can upload file to remote url supplied from remote upload build.', function (done) {

        // Populate the working directory
        rimraf.sync("test/test-content/working");
        fsExtra.copySync("test/test-content/source", "test/test-content/working");


        let sourceService = new SourceService(api, siteConfig);

        let changes: ChangedObject[] = [
            new ChangedObject("test.html", "UPDATE", "EEEFFF")
        ];

        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                /**
                 * Create the remote upload build.
                 */
                sourceService.createRemoteUploadBuild(changes).then((uploadBuild: SourceUploadBuild) => {

                    sourceService.uploadFile("test.html", uploadBuild.uploadUrls["test.html"]).then((status) => {
                        expect(status).toEqual(200);
                        done();
                    });

                });

            });

        });
    });


    it('Can upload file set using remote upload build URLs', function (done) {

        // Populate the working directory
        rimraf.sync("test/test-content/working");
        fsExtra.copySync("test/test-content/source", "test/test-content/working");

        let sourceService = new SourceService(api, siteConfig);


        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                let localFootprints = sourceService.getLocalObjectFootprints();

                sourceService.getRemoteObjectFootprints().then((remoteFootprints) => {

                    let changes = sourceService.generateChanges(remoteFootprints, localFootprints);

                    /**
                     * Create the remote upload build.
                     */
                    sourceService.createRemoteUploadBuild(changes).then((uploadBuild: SourceUploadBuild) => {

                        sourceService.uploadFileSet(uploadBuild.uploadUrls).then((results: any) => {


                            expect(Object.keys(results).length).toEqual(6);
                            expect(results["nobody.html"]).toEqual(200);
                            expect(results["nohead.html"]).toEqual(200);
                            expect(results["test.html"]).toEqual(200);
                            expect(results["test2.html"]).toEqual(200);
                            expect(results["sub/testsub.html"]).toEqual(200);
                            expect(results["sub/nested/testnested.html"]).toEqual(200);

                            done();
                        });

                    });


                });


            });

        });

    });


    it('Can get download URLs from remote system using set of changes', function (done) {

        let changedFiles = [
            new ChangedObject("test.html", "UPDATE", "etyeyertyeyer"),
            new ChangedObject("test2.html", "UPDATE", "6456546436"),
            new ChangedObject("test/my/sub/test3.html", "UPDATE", "234535353252"),
            new ChangedObject("test4.html", "DELETE", ""),
            new ChangedObject("test1.html", "DELETE", "")
        ];


        let sourceManager = new SourceService(api, siteConfig);


        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                sourceManager.getRemoteDownloadUrls(changedFiles).then(downloadUrls => {

                    expect(Object.keys(downloadUrls).length).toEqual(3);
                    expect(downloadUrls["test.html"]).toBeDefined();
                    expect(downloadUrls["test2.html"]).toBeDefined();
                    expect(downloadUrls["test/my/sub/test3.html"]).toBeDefined();

                    done();

                });


            });

        });

    });


    it('Can download single file from remote URL', function (done) {

        rimraf.sync("test/test-content/working");


        let sourceManager = new SourceService(api, siteConfig);

        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                sourceManager.getRemoteDownloadUrls([
                    new ChangedObject("drama.txt", "UPDATE"),
                    new ChangedObject("english.txt", "UPDATE")
                ]).then(downloadUrls => {

                    sourceManager.downloadFile(downloadUrls["drama.txt"], "test/test-content/working/drama.txt").then(status => {
                        expect(status).toEqual(200);
                        expect(fs.existsSync("test/test-content/working/drama.txt")).toBeTruthy();
                        expect(fs.readFileSync("test/test-content/working/drama.txt").toString().trim()).toEqual("DRAMA DRAMA DRAMA !!!");

                        done();
                    });

                });


            });
        });

    });


    it('Can download fileset from remote URLs', function (done) {

        rimraf.sync("test/test-content/working");


        let sourceService = new SourceService(api, siteConfig);

        auth.login().then(result => {

            link.process("oxfordcyberstatictest").then(result => {

                sourceService.getRemoteDownloadUrls([
                    new ChangedObject("drama.txt", "UPDATE"),
                    new ChangedObject("english.txt", "UPDATE")
                ]).then(downloadUrls => {

                    sourceService.downloadFileSet(downloadUrls).then(results => {

                        expect(Object.keys(results).length).toEqual(2);
                        expect(results["drama.txt"]).toEqual(200);
                        expect(results["english.txt"]).toEqual(200);


                        expect(fs.existsSync("test/test-content/working/drama.txt")).toBeTruthy();
                        expect(fs.existsSync("test/test-content/working/english.txt")).toBeTruthy();

                        expect(fs.readFileSync("test/test-content/working/drama.txt").toString().trim()).toEqual("DRAMA DRAMA DRAMA !!!");
                        expect(fs.readFileSync("test/test-content/working/english.txt").toString().trim()).toEqual("ENGLISH IS MY FAVOURITE SUBJECT");

                        done();

                    });

                });

            });

        });


    });


});
