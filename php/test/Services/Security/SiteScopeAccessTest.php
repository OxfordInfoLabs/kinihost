<?php

namespace Kinihost\Services\Security;

use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinihost\TestBase;

include_once "autoloader.php";

class SiteScopeAccessTest extends TestBase {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;


    /**
     * @var SiteScopeAccess
     */
    private $siteScopeAccess;


    public function setUp(): void {
        $this->authenticationService = Container::instance()->get(AuthenticationService::class);
        $this->siteScopeAccess = Container::instance()->get(SiteScopeAccess::class);
    }


    public function testUserRoleOnlyAssignableIfSiteExistsInAccount() {

        // Super user first
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $crossAccountRoles = [new UserRole("SITE", 7, 1, 1, 2)];
        $this->assertEquals([], $this->siteScopeAccess->getAssignableUserRoles($crossAccountRoles));

        $inAccountRoles = [new UserRole("SITE", 1, 1, 1, 2)];
        $this->assertEquals($inAccountRoles, $this->siteScopeAccess->getAssignableUserRoles($inAccountRoles));


        // Account admin first
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $crossAccountRoles = [new UserRole("SITE", 7, 1, 1, 2)];
        $this->assertEquals([], $this->siteScopeAccess->getAssignableUserRoles($crossAccountRoles));

        $inAccountRoles = [new UserRole("SITE", 1, 1, 1, 2)];
        $this->assertEquals($inAccountRoles, $this->siteScopeAccess->getAssignableUserRoles($inAccountRoles));


    }




    public function testCanGetFilteredScopeObjectDescriptions() {

        // Super user first
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $filteredDescriptions = $this->siteScopeAccess->getFilteredScopeObjectDescriptions("", 0, 10, 1);
        $this->assertEquals(6, sizeof($filteredDescriptions));

        $this->assertEquals("Lucien Taylor (lucientaylor)", $filteredDescriptions[4]);
        $this->assertEquals("Mark Robertshaw (markrobertshaw)", $filteredDescriptions[5]);
        $this->assertEquals("Nathan Alan (nathanalan)", $filteredDescriptions[6]);
        $this->assertEquals("Pingu Graphics (pingu)", $filteredDescriptions[3]);
        $this->assertEquals("Sam Davis Design .COM (samdavisdotcom)", $filteredDescriptions[1]);
        $this->assertEquals("Woollen Mill Site (woollenmill)", $filteredDescriptions[2]);


        // Limited
        $filteredDescriptions = $this->siteScopeAccess->getFilteredScopeObjectDescriptions("", 0, 3, 1);
        $this->assertEquals(3, sizeof($filteredDescriptions));

        $this->assertEquals("Lucien Taylor (lucientaylor)", $filteredDescriptions[4]);
        $this->assertEquals("Mark Robertshaw (markrobertshaw)", $filteredDescriptions[5]);
        $this->assertEquals("Nathan Alan (nathanalan)", $filteredDescriptions[6]);


        // Offset
        $filteredDescriptions = $this->siteScopeAccess->getFilteredScopeObjectDescriptions("", 4, 10, 1);
        $this->assertEquals(2, sizeof($filteredDescriptions));

        $this->assertEquals("Sam Davis Design .COM (samdavisdotcom)", $filteredDescriptions[1]);
        $this->assertEquals("Woollen Mill Site (woollenmill)", $filteredDescriptions[2]);

        // Filtered
        $filteredDescriptions = $this->siteScopeAccess->getFilteredScopeObjectDescriptions("l", 0, 10, 1);
        $this->assertEquals(3, sizeof($filteredDescriptions));
        $this->assertEquals("Lucien Taylor (lucientaylor)", $filteredDescriptions[4]);
        $this->assertEquals("Nathan Alan (nathanalan)", $filteredDescriptions[6]);
        $this->assertEquals("Woollen Mill Site (woollenmill)", $filteredDescriptions[2]);


    }


}
