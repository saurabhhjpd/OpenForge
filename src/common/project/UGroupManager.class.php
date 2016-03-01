<?php
/**
 * Copyright (c) Enalean, 2011 - 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

require_once('www/project/admin/ugroup_utils.php');
require_once('www/include/account.php');

class UGroupManager {
    
    /**
     * @var UGroupDao
     */
    private $dao;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(UGroupDao $dao = null, EventManager $event_manager = null) {
        $this->dao           = $dao;
        $this->event_manager = $event_manager;
    }

    /**
     *
     * @param Project $project
     * @param type $ugroup_id
     *
     * @return ProjectUGroup
     */
    public function getUGroupWithMembers(Project $project, $ugroup_id) {
        $ugroup = $this->getUGroup($project, $ugroup_id);
        $ugroup->getMembers();

        return $ugroup;
    }

    /**
     * @return ProjectUGroup of the given project or null if not found
     */
    public function getUGroup(Project $project, $ugroup_id) {
        $project_id = $project->getID();
        if ($ugroup_id <= 100) {
            $project_id = 100;
        }

        $row = $this->getDao()->searchByGroupIdAndUGroupId($project_id, $ugroup_id)->getRow();
        if ($row) {
            return $this->instanciateGroupForProject($project, $row);
        }
    }

    private function instanciateGroupForProject(Project $project, array $row) {
        // force group_id as it is set to 100 for dynamic groups
        $row['group_id'] = $project->getID();
        return new ProjectUGroup($row);
    }

    /**
     *
     * @param Project $project
     * @param array $excluded_ugroups_id
     * @return ProjectUGroup[]
     */
    public function getUGroups(Project $project, array $excluded_ugroups_id = array()) {
        $ugroups = array();
        foreach ($this->getDao()->searchDynamicAndStaticByGroupId($project->getId()) as $row) {
            if (in_array($row['ugroup_id'], $excluded_ugroups_id)) {
                continue;
            }
            $ugroups[] = $this->instanciateGroupForProject($project, $row);
        }
        return $ugroups;
    }

    /**
     * @return ProjectUGroup[]
     */
    public function getStaticUGroups(Project $project) {
        $ugroups = array();
        foreach ($this->getDao()->searchStaticByGroupId($project->getId()) as $row) {
            $ugroups[] = $this->instanciateGroupForProject($project, $row);
        }
        return $ugroups;
    }

    /**
     * @return ProjectUGroup
     */
    public function getUGroupByName(Project $project, $name) {
        $row = $this->getDao()->searchByGroupIdAndName($project->getID(), $name)->getRow();
        if (! $row && preg_match('/^ugroup_.*_key$/', $name)) {
            $row = $this->getDao()->searchByGroupIdAndName(100, $name)->getRow();
        }
        if (! $row && in_array($this->getUnormalisedName($name), User_ForgeUGroup::$names)) {
            $row = $this->getDao()->searchByGroupIdAndName(100, $this->getUnormalisedName($name))->getRow();
        }
        if (! $row && $ugroup = $this->getDynamicUGoupByName($project, $name)) {
            return $ugroup;
        }
        if ($row) {
            return new ProjectUGroup($row);
        }
        return null;
    }

    public function getDynamicUGoupIdByName($name) {
        return array_search($name, ProjectUGroup::$normalized_names);
    }

    public function getDynamicUGoupByName(Project $project, $name) {
        $ugroup_id = $this->getDynamicUGoupIdByName($name);
        if(empty($ugroup_id)) { return null; }
        return new ProjectUGroup(array(
            'ugroup_id' => $ugroup_id,
            'name'      => $name,
            'group_id'  => $project->getID()
        ));
    }

    private function getUnormalisedName($name) {
        return 'ugroup_'.$name.'_name_key';
    }

    public function getLabel($group_id, $ugroup_id) {
        $row = $this->getDao()->searchNameByGroupIdAndUGroupId($group_id, $ugroup_id)->getRow();
        if (! $row) {
            return '';
        }

        return $row['name'];
    }

    /**
     * Return all UGroups the user belongs to
     *
     * @param PFUser $user The user
     *
     * @return ProjectUGroup[]
     */
    public function getByUserId($user) {
        $ugroups = array();
        $dar     = $this->getDao()->searchByUserId($user->getId());

        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                $ugroups [] = new ProjectUGroup($row);
            }
        }

        return $ugroups;
    }

    /**
     * Returns a ProjectUGroup from its Id
     *
     * @param Integer $ugroupId The UserGroupId
     * 
     * @return ProjectUGroup
     */
    public function getById($ugroupId) {
        $dar = $this->getDao()->searchByUGroupId($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new ProjectUGroup($dar->getRow());
        } else {
            return new ProjectUGroup();
        }
    }

    /**
     * Wrapper for UGroupDao
     *
     * @return UGroupDao
     */
    public function getDao() {
        if (!$this->dao) {
            $this->dao = new UGroupDao();
        }
        return $this->dao;
    }

    /**
     * Wrapper for EventManager
     *
     * @return EventManager
     */
    private function getEventManager() {
        if (! $this->event_manager) {
            $this->event_manager = EventManager::instance();
        }
        return $this->event_manager;
    }

    /**
     * Get Dynamic ugroups members
     *
     * @param Integer $ugroupId Id of the ugroup
     * @param Integer $groupId  Id of the project
     *
     * @return array of User
     */
    public function getDynamicUGroupsMembers($ugroupId, $groupId) {
        if ($ugroupId > 100) {
            return array();
        }
        $um = UserManager::instance();
        $users   = array();
        $dao     = new UGroupUserDao();
        $members = $dao->searchUserByDynamicUGroupId($ugroupId, $groupId);
        if ($members && !$members->isError()) {
            foreach ($members as $member) {
                $users[] = $um->getUserById($member['user_id']);
            }
        }
        return $users;
    }

    /**
     * @param PFUSer $user
     * @param int $ugroup_id
     * @param int $group_id
     * @return boolean
     */
    public function isDynamicUGroupMember(PFUSer $user, $ugroup_id, $group_id) {
        $dao = new UGroupUserDao();

        return $dao->isDynamicUGroupMember($user->getId(), $ugroup_id, $group_id);
    }

    /**
     * Check if update users is allowed for a given user group
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return boolean
     */
    public function isUpdateUsersAllowed($ugroupId) {
        $ugroupUpdateUsersAllowed = true;
        $this->getEventManager()->processEvent(Event::UGROUP_UPDATE_USERS_ALLOWED, array('ugroup_id' => $ugroupId, 'allowed' => &$ugroupUpdateUsersAllowed));
        return $ugroupUpdateUsersAllowed;
    }

    /**
     * Wrapper for dao method that checks if the user group is valid
     *
     * @param Integer $groupId  Id of the project
     * @param Integer $ugroupId Id of the user goup
     *
     * @return boolean
     */
    public function checkUGroupValidityByGroupId($groupId, $ugroupId) {
        return $this->getDao()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Wrapper for dao method that retrieves all Ugroups bound to a given ProjectUGroup
     *
     * @param Integer $ugroupId Id of the user goup
     *
     * @return DataAccessResult
     */
    public function searchUGroupByBindingSource($ugroupId) {
        return $this->getDao()->searchUGroupByBindingSource($ugroupId);
    }

    /**
     * Wrapper for dao method that updates binding option for a given ProjectUGroup
     *
     * @param Integer $ugroup_id Id of the user group
     * @param Integer $source_ugroup_id Id of the user group we should bind to
     *
     * @return Boolean
     */
    public function updateUgroupBinding($ugroup_id, $source_ugroup_id = null) {
        $ugroup = $this->getById($ugroup_id);
        if ($source_ugroup_id === null) {
            $this->getEventManager()->processEvent(
                Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE,
                array(
                    'ugroup' => $ugroup
                )
            );
        } else {
            $source = $this->getById($source_ugroup_id);
            $this->getEventManager()->processEvent(
                Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD,
                array(
                    'ugroup' => $ugroup,
                    'source' => $source,
                )
            );
        }
        return $this->getDao()->updateUgroupBinding($ugroup_id, $source_ugroup_id);
    }

    /**
     * Wrapper to retrieve the source user group from a given bound ugroup id
     *
     * @param Integer $ugroupId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function getUgroupBindingSource($ugroupId) {
        $dar = $this->getDao()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return new ProjectUGroup($dar->getRow());
        } else {
            return null;
        }
    }

    /**
     * Wrapper for UserGroupDao
     *
     * @return UserGroupDao
     */
    public function getUserGroupDao() {
        return new UserGroupDao();
    }

    /**
     * Return name and id of all ugroups belonging to a specific project
     *
     * @param Integer $groupId    Id of the project
     * @param Array   $predefined List of predefined ugroup id
     *
     * @return DataAccessResult
     */
    public function getExistingUgroups($groupId, $predefined = null) {
        $dar = $this->getUserGroupDao()->getExistingUgroups($groupId, $predefined);
        if ($dar && !$dar->isError()) {
            return $dar;
        }
        return array();
    }

    public function createEmptyUgroup($project_id, $ugroup_name, $ugroup_description) {
        return ugroup_create($project_id, $ugroup_name, $ugroup_description, "cx_empty");
    }

    public function addUserToUgroup($project_id, $ugroup_id, $user_id) {
        return ugroup_add_user_to_ugroup($project_id, $ugroup_id, $user_id);
    }

    public function syncUgroupMembers(ProjectUGroup $user_group, array $users_from_references) {
        $this->getDao()->startTransaction();

        $current_members   = $this->getUgroupMembers($user_group);
        $members_to_remove = $this->getUsersToRemove($current_members, $users_from_references);
        $members_to_add    = $this->getUsersToAdd($current_members, $users_from_references);

        foreach ($members_to_remove as $member_to_remove) {
            $this->removeUserFromUserGroup($user_group, $member_to_remove);
        }

        foreach ($members_to_add as $member_to_add) {
            $this->addUserToUserGroup($user_group, $member_to_add);
        }

        $this->getDao()->commit();
    }

    /**
     * @return array
     */
    private function getUgroupMembers(ProjectUGroup $user_group) {
        $members = array();

        foreach ($user_group->getMembersIncludingSuspended() as $member) {
            $members[] = $member;
        }

        return $members;
    }

    private function getUsersToRemove(array $current_members, array $users_from_references) {
        return array_diff($current_members, $users_from_references);
    }

    private function getUsersToAdd(array $current_members, array $users_from_references) {
        return array_diff($users_from_references, $current_members);
    }

    private function addUserToUserGroup(ProjectUGroup $user_group, PFUser $user) {
        if ($user_group->getId() == ProjectUGroup::PROJECT_MEMBERS) {
            return account_add_user_obj_to_group($user_group->getProjectId(), $user);
        }

        return $user_group->addUser($user);
    }

    private function removeUserFromUserGroup(ProjectUGroup $user_group, PFUser $user) {
        if ($user_group->getId() == ProjectUGroup::PROJECT_MEMBERS) {
            return account_remove_user_from_group($user_group->getProjectId(), $user->getId());
        }

        return $user_group->removeUser($user);
    }
}