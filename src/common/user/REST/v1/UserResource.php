<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\User\REST\v1;

use PFUser;
use UserManager;
use UGroupLiteralizer;
use PaginatedUserCollection;
use Tuleap\User\REST\UserRepresentation as UserRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\UserManager as RestUserManager;
use Luracast\Restler\RestException;
use User_ForgeUserGroupPermission_RetrieveUserMembershipInformation;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsManager;
use User_ForgeUserGroupPermissionsDao;
use Tuleap\REST\AuthenticatedResource;

/**
 * Wrapper for users related REST methods
 */
class UserResource extends AuthenticatedResource {

    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;

    /** @var UserManager */
    private $user_manager;

    /** @var JsonDecoder */
    private $json_decoder;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var \Tuleap\REST\UserManager */
    private $rest_user_manager;

    /** @var User_ForgeUserGroupPermissionsManager */
    private $forge_ugroup_permissions_manager;

    public function __construct() {
        parent::__construct();
        $this->user_manager       = UserManager::instance();
        $this->json_decoder       = new JsonDecoder();
        $this->ugroup_literalizer = new UGroupLiteralizer();
        $this->rest_user_manager  = RestUserManager::build();

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * Get a user
     *
     * Get the definition of a given user.
     * <pre> Note that when accessing this route without authentication certain properties<br>
     * will not be returned in the response.
     * </pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the desired user
     *
     * @throws 400
     * @throws 403
     * @throws 404
     *
     * @return UserRepresentation {@type UserRepresentation}
     */
    public function getId($id) {
        $this->checkAccess();

        $user                = $this->getUserById($id);
        $user_representation = ($this->is_authenticated) ? new UserRepresentation() : new MinimalUserRepresentation();
        return $user_representation->build($user);
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the user
     *
     * @access public
     *
     * @throws 400
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeaders();
    }

    /**
     * @url OPTIONS
     *
     * @access public
     */
    public function options() {
        $this->sendAllowHeaders();
    }

    /**
     * Get users
     *
     * Get all users matching the query.
     * <pre> Note that when accessing this route without authentication certain properties<br>
     * will not be returned in the response.
     * </pre>
     *
     * <br>
     * ?query can be either:
     * <ul>
     *   <li>a simple string, then it will search on "real_name" and "username" with wildcard</li>
     *   <li>a json object to search on username with exact match: {"username": "john_doe"}</li>
     * </ul>
     *
     * @access hybrid
     *
     * @param string $query  Search string (3 chars min in length) {@from query} {@min 3}
     * @param int    $limit  Number of elements displayed per page
     * @param int    $offset Position of the first element to display
     *
     * @return array {@type UserRepresentation}
     */
    public function get(
        $query,
        $limit = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkAccess();
        if ($this->json_decoder->looksLikeJson($query)) {
            $user_collection = $this->getUserFromExactSearch($query);
        } else {
            $user_collection = $this->getUsersFromPatternSearch($query, $offset, $limit);
        }

        return $this->getUsersListRepresentation($user_collection, $offset, $limit);
    }

    private function getUserFromExactSearch($query) {
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query['username'])) {
            throw new RestException(400, 'You can only search on "username"');
        }
        $user  = $this->user_manager->getUserByUserName($json_query['username']);
        $users = array();
        if ($user !== null) {
            $users[] = $user;
        }
        return new PaginatedUserCollection(
            $users,
            count($users)
        );
    }

    private function getUsersFromPatternSearch($query, $offset, $limit) {
        $exact = false;
        return $this->user_manager->getPaginatedUsersByUsernameOrRealname(
            $query,
            $exact,
            $offset,
            $limit
        );
    }

    private function getUsersListRepresentation(PaginatedUserCollection $user_collection, $offset, $limit) {
        $this->sendAllowHeaders();
        Header::sendPaginationHeaders(
            $limit,
            $offset,
            $user_collection->getTotalCount(),
            self::MAX_LIMIT
        );

        $list_of_user_representation = array();
        foreach ($user_collection->getUsers() as $user) {
            $user_representation = ($this->is_authenticated) ? new UserRepresentation() : new MinimalUserRepresentation();
            $list_of_user_representation[] = $user_representation->build($user);
        }

        return $list_of_user_representation;
    }

    /**
     * Get the list of user groups the given user is member of
     *
     * This list of groups is displayed as an array of string:
     * <pre>
     * [
     *     "site_active",
     *     "%project-name%_project_members",
     *     "%project-name%_project_admin",
     *     "ug_101"
     *     ...
     * ]
     * </pre>
     *
     * @url GET {id}/membership
     * @access protected
     *
     * @param int $id Id of the desired user
     *
     * @throws 400
     * @throws 403
     * @throws 404
     *
     * @return array {@type string}
     */
    public function getMembership($id) {
        $this->checkAccess();

        $watchee = $this->getUserById($id);
        $watcher = $this->rest_user_manager->getCurrentUser();
        if ($this->checkUserCanSeeOtherUser($watcher, $watchee)) {
            return $this->ugroup_literalizer->getUserGroupsForUser($watchee);
        }
        throw new RestException(403, "Cannot see other's membreship");
    }

    /**
     * @url OPTIONS {id}/preferences
     *
     * @param int $id Id of the user
     *
     * @access public
     */
    public function optionPreferences($id) {
        Header::allowOptionsGetPatch();
    }

    /**
     * Get a user preference
     *
     * @url GET {id}/preferences
     *
     * @access hybrid
     *
     * @param int    $id  Id of the desired user
     * @param string $key Preference key
     *
     * @throws 401
     * @throws 404
     *
     * @return UserPreferenceRepresentation
     */
    public function getPreferences($id, $key) {
        $this->checkAccess();

        $value = $this->getUserPreference($id, $key);

        $preference_representation = new UserPreferenceRepresentation();
        $preference_representation->build($key, $value);

        return $preference_representation;

    }

    /**
     * Set a user preference
     *
     * @url PATCH {id}/preferences
     *
     * @access hybrid
     *
     * @param int $id Id of the desired user
     * @param UserPreferenceRepresentation $preference Preference representation {@from body}
     *
     * @throws 401
     * @throws 500
     *
     * @return UserPreferenceRepresentation
     */
    public function patchPreferences($id, $preference) {
        $this->checkAccess();

        if ($id != $this->rest_user_manager->getCurrentUser()->getId()) {
            throw new RestException(403, 'You can only set your own preferences');
        }

        if (! $this->setUserPreference($id, $preference->key, $preference->value)) {
            throw new RestException(500, 'Unable to set the user preference');
        }
    }

    private function getUserPreference($user_id, $key) {
        return $this->user_manager->getUserById($user_id)->getPreference($key);
    }

    private function setUserPreference($user_id, $key, $value) {
        return $this->user_manager->getUserById($user_id)->setPreference($key, $value);
    }

    private function checkUserCanSeeOtherUser(PFUser $watcher, PFuser $watchee) {
        if ($watcher->isSuperUser()) {
            return true;
        }
        if ($watcher->getId() === $watchee->getId()) {
            return true;
        }

        return ($this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $watcher, new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation()
        ) || $this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $watcher, new User_ForgeUserGroupPermission_UserManagement()
        ));
    }


    /**
     * Partial update of user details
     *
     * Things to take into account:
     * <ol>
     *  <li>You don't need to set all 'values' of the user, you can restrict to the modified ones</li>
     *  <li>Possible fields are:"email", "real_name", "username" and "status"
     *  <li>Examples: To update a user status and username, the values must be an array:
     * <pre>
     * {
     * "status" : "S"
     * ,
     *
     * "username": "johnd"
     * }
     * </pre>
     * </li>
     * </ol>
     *
     * @url PATCH {id}
     * @param string  $id        Id of the user
     * @param Array   $values    User fields values
     *
     */
    protected function patchUserDetails($id, array $values) {
        $watchee = $this->getUserById($id);
        $watcher = $this->rest_user_manager->getCurrentUser();
        if ($this->checkUserCanUpdateOtherUser($watcher, $watchee)) {
            foreach ($values as $key => $value){
                switch ($key) {
                    case "status":
                        $watchee->setStatus($value);
                    break;

                    case "email":
                        $watchee->setEmail($value);
                    break;

                    case "real_name":
                        $watchee->setRealName($value);
                    break;

                    case "username":
                        $watchee->setUserName($value);
                    break;

                    default:
                        break;
                }
            }
            return $this->user_manager->updateDb($watchee);
        }
        throw new RestException(403, "Cannot update other's details");
    }

    /**
     * Check if user has permission to update user details
     * @param PFUser $watcher
     * @param PFUSER $watchee
     *
     * @return Boolean
     *
     */
     private function checkUserCanUpdateOtherUser(PFUser $watcher, PFuser $watchee) {
        if ($watcher->isSuperUser()) {
            return true;
        }

        return $this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $watcher, new User_ForgeUserGroupPermission_UserManagement()
        );
    }

    private function getUserById($id) {
        $user = $this->user_manager->getUserById($id);

        if (! $user) {
            throw new RestException(404, 'User Id not found');
        }

        return $user;
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGetPatch();
    }
}
