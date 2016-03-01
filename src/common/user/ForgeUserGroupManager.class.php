<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUserGroupManager {

    /**
     * @var UserGroupDao
     */
    private $dao;

    public function __construct(UserGroupDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return bool
     */
    public function deleteForgeUserGroup(User_ForgeUGroup $user_group) {
        return $this->dao->deleteForgeUGroup($user_group->getId());
    }

    /**
     * @return boolean
     * @throws User_UserGroupNotFoundException
     * @throws User_UserGroupNameInvalidException
     */
    public function updateUserGroup(User_ForgeUgroup $user_group) {
        $row = $this->dao->getForgeUGroup($user_group->getId());
        if (! $row) {
            throw new User_UserGroupNotFoundException($user_group->getId());
        }

        if (! $this->userGroupHasModifications($user_group, $row)) {
            return true;
        }

        return $this->dao->updateForgeUGroup(
            $user_group->getId(),
            $user_group->getName(),
            $user_group->getDescription()
        );
    }

    private function userGroupHasModifications(User_ForgeUgroup $user_group, $row) {
        return $user_group->getName() != $row['name'] ||
            $user_group->getDescription() != $row['description'];
    }
}
