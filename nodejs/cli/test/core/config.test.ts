import Config from "../../src/Core/Config";

describe('Tests for the config class', function () {


    it('Config populated with existing user access token from config file if set', function () {

        let config = new Config("useraccesstoken.example", "dev", "test");

        expect(config.userToken).toEqual("TESTTOKEN");
    });


});
