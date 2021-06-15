<?php

namespace Kinihost\Services\Security;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Security\User;
use Kiniauth\Objects\Security\UserRole;
use Kiniauth\Services\Security\ScopeAccess;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Monolog\Logger;
use Kinihost\Objects\Site\Site;
use Kinihost\ValueObjects\Site\SiteSummary;

/**
 * @noProxy
 *
 * Class SiteScopeAccess
 * @package Kinihost\Services\Security
 */
class SiteScopeAccess extends ScopeAccess {

    const SCOPE_SITE = "SITE";

    /**
     * AccountScopeAccess constructor.
     *
     */
    public function __construct() {
        parent::__construct(self::SCOPE_SITE, "Static Website", "siteId");
    }


    /**
     * Generate scope privileges from either a user or an account (only one will be passed).
     * if an account is passed, it means it is an account based log in so will generally be granted full access to account items.
     *
     * This is used on log in to determine access to items of this scope type.  It should return an array of privilege keys indexed by the id of the
     * scope item.  The indexed array of account privileges is passed through for convenience.
     *
     * Use * as the scope key to indicate all accounts.
     *
     * @param User $user
     * @param Account $account
     * @param string[] $accountPrivileges
     *
     * @return
     */
    public function generateScopePrivileges($user, $account, $accountPrivileges) {

        $scopePrivileges = [];

        // All superusers have "access" privilege to all sites.
        foreach ($accountPrivileges as $accountId => $privileges) {
            if (in_array("*", $privileges)) {
                $scopePrivileges["*"] = ["access"];
            }
        }

        // If super user logged in, grant full access
        if (isset($accountPrivileges["*"][0]) && $accountPrivileges["*"][0] == "*") {
            $scopePrivileges["*"] = ["*"];
        } else if ($user) {
            /**
             * @var $role UserRole
             */
            foreach ($user->getRoles() as $role) {

                if ($role->getScope() == self::SCOPE_SITE) {

                    if (!isset($scopePrivileges[$role->getScopeId()])) {
                        $scopePrivileges[$role->getScopeId()] = [];
                    }

                    $scopePrivileges[$role->getScopeId()] = array_merge($scopePrivileges[$role->getScopeId()], $role->getPrivileges());
                }
            }

        }


        return $scopePrivileges;
    }

    /**
     * Check which of the supplied user roles are assignable
     *
     * @param UserRole[] $userRoles
     * @return UserRole[]
     */
    public function getAssignableUserRoles($userRoles) {

        if (sizeof($userRoles) === 0) {
            return $userRoles;
        }
        // Get the site id and load the site.
        $siteIds = ObjectArrayUtils::getMemberValueArrayForObjects("scopeId", $userRoles);

        $indexedRoles = ObjectArrayUtils::indexArrayOfObjectsByMember(["scopeId", "userId"], $userRoles);


        // Grab site objects representing these site ids.
        $accessibleSites = Site::multiFetch($siteIds, true);


        $clauses = [];
        foreach ($indexedRoles as $siteId => $siteUserRoles) {
            foreach ($siteUserRoles as $userId => $role) {
                $clause = "(scopeId = $siteId";
                if ($userId) $clause .= " AND userId <> $userId";
                $clause .= ")";
                $clauses[] = $clause;
            }
        }
        $clauses = "(" . join(" OR ", $clauses) . ")";


        $siteUsers = UserRole::values(["scopeId", "COUNT(DISTINCT(userId)) users"],
            "WHERE scope = ? AND $clauses GROUP BY scopeId",
            self::SCOPE_SITE);
        $indexedSiteUsers = [];
        foreach ($siteUsers as $siteUser) {
            $indexedSiteUsers[$siteUser["scopeId"]] = $siteUser["users"];
        }

        $returnUserRoles = [];


        foreach ($userRoles as $userRole) {

            // Continue if no site accessible.
            if (!isset($accessibleSites[$userRole->getScopeId()]))
                continue;

            $site = $accessibleSites[$userRole->getScopeId()];

            // Continue if site in wrong account
            if ($site->getAccountId() != $userRole->getAccountId())
                continue;

            $returnUserRoles[] = $userRole;

        }


        return $returnUserRoles;


    }


    /**
     * Return labels matching each scope id.  This enables the generic role assignment screen
     * to show sensible values.
     *
     * @param $scopeIds
     * @param null $accountId
     * @return mixed
     */
    public function getScopeObjectDescriptionsById($scopeIds, $accountId = null) {

        $sites = Site::multiFetch($scopeIds);
        return ObjectArrayUtils::getMemberValueArrayForObjects("description", $sites);

    }


    /**
     * Get filtered scope object descriptions with offset and limiting for paging purposes.  If supplied, the
     * account id will be used to filter these if required.
     *
     * @param string $searchFilter
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     */
    public function getFilteredScopeObjectDescriptions($searchFilter, $offset = 0, $limit = 10, $accountId = null) {

        $likeString = "%$searchFilter%";
        $offset = $offset ? $offset : 0;

        $results = Site::filter("WHERE (title LIKE ? or siteKey LIKE ?) AND accountId = ? ORDER BY title LIMIT $limit OFFSET $offset",
            $likeString, $likeString, $accountId);

        $results = ObjectArrayUtils::indexArrayOfObjectsByMember("siteId", $results);

        return ObjectArrayUtils::getMemberValueArrayForObjects("description", $results);

    }


}
