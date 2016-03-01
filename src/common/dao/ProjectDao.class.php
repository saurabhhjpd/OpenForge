<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class ProjectDao extends DataAccessObject {

    const TABLE_NAME       = 'groups';
    const GROUP_ID         = 'group_id';
    const STATUS           = 'status';
    const UNIX_GROUP_NAME  = 'unix_group_name';

    public function __construct($da = null) {
        parent::__construct($da);
        $this->table_name = 'groups';
    }

    public function searchById($id) {
        $sql = "SELECT *".
               " FROM ".$this->table_name.
               " WHERE group_id = ".$this->da->quoteSmart($id);
        return $this->retrieve($sql);
    }

    public function searchByStatus($status) {
        $status = $this->da->quoteSmart($status);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE status = $status
                ORDER BY group_name";
        return $this->retrieve($sql);
    }
    
    public function searchByUnixGroupName($unixGroupName){
        $unixGroupName= $this->da->quoteSmart($unixGroupName);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE unix_group_name=$unixGroupName";
        return $this->retrieve($sql);
    }

    public function searchByCaseInsensitiveUnixGroupName($unixGroupName) {
        $unixGroupName= $this->da->quoteSmart($unixGroupName);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE LOWER(unix_group_name)=LOWER($unixGroupName)";
        return $this->retrieve($sql);
    }

    /**
     * Look for active projects, based on their name (unix/public)
     * 
     * This method returns only active projects. If no $userId provided, only
     * public project are returned.
     * If $userId is provided, both public and private projects the user is member
     * of are returned
     * If $userId is provided, you can also choose to restrict the result set to
     * the projects the user is member of or is admin of.
     * 
     * @param String  $name
     * @param Integer $limit
     * @param Integer $userId
     * @param Boolean $isMember
     * @param Boolean $isAdmin
     * @param Boolean $isPrivate Display private projects if true
     *
     * @return DataAccessResult
     */
    public function searchProjectsNameLike($name, $limit, $userId=null, $isMember=false, $isAdmin=false, $isPrivate = false) {
        $join    = '';
        $where   = '';
        $groupby = '';
        $public  = ' g.access != '.$this->da->quoteSmart(Project::ACCESS_PRIVATE);
        if ($isPrivate) {
            $public  = ' 1 ';
        }
        if ($userId != null) {
            if ($isMember || $isAdmin) {
                // Manage if we search project the user is member or admin of
                $join  .= ' JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND ug.user_id = '.$this->da->escapeInt($userId);
                if ($isAdmin) {
                    $where .= ' AND ug.admin_flags = "A"';
                }
            } else {
                // Either public projects or private projects the user is member of
                $join  .= ' LEFT JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND ('.$public.
                          '      OR (g.access = '.$this->da->quoteSmart(Project::ACCESS_PRIVATE).' AND ug.user_id = '.$this->da->escapeInt($userId).'))';
            }
            $groupby .= ' GROUP BY g.group_id';
        } else {
            // If no user_id provided, only return public projects
            $where .= ' AND '.$public;
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS g.*".
               " FROM ".$this->table_name." g".
               $join.
               " WHERE (g.group_name like ".$this->da->quoteSmart($name.'%').
               " OR g.unix_group_name like ".$this->da->quoteSmart($name.'%').")".
               " AND g.status='A'".
               $where.
               $groupby.
               " ORDER BY group_name".
               " LIMIT ".$this->da->escapeInt($limit);
        return $this->retrieve($sql);
    }

    public function searchSiteTemplates() {
        $sql = "SELECT *
         FROM groups
         WHERE type='2'
             AND status IN ('A','s')";
        return $this->retrieve($sql);
    }

    public function searchProjectsUserIsAdmin($user_id) {
        return $this->searchActiveProjectsByUserStatus($user_id, "AND user_group.admin_flags = 'A'");
    }

    public function searchActiveProjectsForUser($user_id) {
        return $this->searchActiveProjectsByUserStatus($user_id);
    }

    private function searchActiveProjectsByUserStatus($user_id, $where = '') {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT groups.*
            FROM groups
              JOIN user_group USING (group_id)
            WHERE user_group.user_id = $user_id
              $where
              AND groups.status='A'
            ORDER BY groups.group_name ASC";
        return $this->retrieve($sql);
    }

    public function updateStatus($id, $status) {
        $sql = 'UPDATE groups'.
            ' SET status = '.$this->da->quoteSmart($status).
            ' WHERE group_id = '.$this->da->escapeInt($id);
        return $this->update($sql);
    }

    /**
     * Update the http_domain and service when renaming the group
     * @param Project $project
     * @param String  $new_name
     * @return Boolean
     */
    public function renameProject($project,$new_name){
        //Update 'groups' table
        $sql = ' UPDATE groups SET unix_group_name= '.$this->da->quoteSmart($new_name).' , 
                 http_domain=REPLACE (http_domain,'.$this->da->quoteSmart($project->getUnixName(false)).','.$this->da->quoteSmart($new_name).')
                 WHERE group_id= '.$this->da->quoteSmart($project->getID());
        $res_groups = $this->update($sql);

        //Update 'service' table
        if ($res_groups){
            $sql_summary  = ' UPDATE service SET link= REPLACE (link,'.$this->da->quoteSmart($project->getUnixName()).','.$this->da->quoteSmart(strtolower($new_name)).')
                              WHERE short_name="summary"
                              AND group_id= '.$this->da->quoteSmart($project->getID());
            $res_summary = $this->update($sql_summary);
            if ($res_summary){
                $sql_homePage = ' UPDATE service SET link= REPLACE (link,'.$this->da->quoteSmart($project->getUnixName()).','.$this->da->quoteSmart(strtolower($new_name)).')
                                  WHERE short_name="homepage"
                                  AND group_id= '.$this->da->quoteSmart($project->getID());
                return $this->update($sql_homePage);
                }
            }
        return false;
    }

    public function renameProjectPluginServiceLink($project_id, $plugin_name, $new_link) {
        $plugin_name = $this->da->quoteSmart($plugin_name);
        $new_link    = $this->da->quoteSmart($new_link);
        $project_id  = $this->da->escapeInt($project_id);

        $sql = "UPDATE service
           SET link = $new_link
           WHERE short_name = $plugin_name
           AND group_id = $project_id";

        return $this->update($sql);
    }
    
    /**
     * Return all projects matching given parameters
     * 
     * @param Integer $offset
     * @param Integer $limit
     * @param String  $status
     * @param String  $groupName
     *
     * @return Array ('projects' => DataAccessResult, 'numrows' => int)
     */
    public function returnAllProjects($offset, $limit, $status=false, $groupName=false) {
        $cond = array();
        if ($status != false) {
            $cond[] = 'status='.$this->da->quoteSmart($status);
        }
        
        if ($groupName != false) {
            $pattern = $this->da->quoteSmart('%'.$groupName.'%');
            $cond[] = '(group_name LIKE '.$pattern.' OR group_id LIKE '.$pattern.' OR unix_group_name LIKE '.$pattern.')';
        }
        
        if (count($cond) > 0) {
            $stm = ' WHERE '.implode(' AND ', $cond);
        } else {
            $stm = '';
        }
        
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM groups '.$stm.'
                ORDER BY group_name 
                ASC LIMIT '.$this->da->escapeInt($offset).', '.$this->da->escapeInt($limit);

        return array('projects' => $this->retrieve($sql), 'numrows' => $this->foundRows());
    }

    public function getMyAndPublicProjectsForREST(PFUser $user, $offset, $limit) {
        $user_id      = $this->da->escapeInt($user->getId());
        $offset       = $this->da->escapeInt($offset);
        $limit        = $this->da->escapeInt($limit);
        $private_type = $this->da->quoteSmart(Project::ACCESS_PRIVATE);

        $sql = "SELECT DISTINCT groups.*
                FROM groups
                  JOIN user_group USING (group_id)
                WHERE status = 'A'
                  AND group_id > 100
                  AND (access != $private_type
                    OR user_group.user_id = $user_id)
                ORDER BY group_id ASC
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function getAllMyAndPublicProjects(PFUser $user) {
        $user_id      = $this->da->escapeInt($user->getId());
        $private_type = $this->da->quoteSmart(Project::ACCESS_PRIVATE);

        $sql = "SELECT DISTINCT groups.*
                FROM groups
                  JOIN user_group USING (group_id)
                WHERE status = 'A'
                  AND group_id > 100
                  AND (access != $private_type
                    OR user_group.user_id = $user_id)
                ORDER BY group_id ASC";

        return $this->retrieve($sql);
    }

    public function countMyAndPublicProjectsForREST(PFUser $user) {
        $user_id      = $this->da->escapeInt($user->getId());
        $private_type = $this->da->quoteSmart(Project::ACCESS_PRIVATE);

        $sql = "SELECT count(DISTINCT group_id) AS 'count_projects'
                FROM groups
                  JOIN user_group USING (group_id)
                WHERE status = 'A'
                  AND group_id > 100
                  AND (access != $private_type
                    OR user_group.user_id = $user_id)";

        return $this->retrieve($sql);
    }

    public function searchByPublicStatus($is_public){
        if ($is_public) {
            $access_clause = 'access != '.$this->da->quoteSmart(Project::ACCESS_PRIVATE);
        } else {
            $access_clause = 'access = '.$this->da->quoteSmart(Project::ACCESS_PRIVATE);
        }

        $sql = "SELECT *
                FROM $this->table_name
                WHERE $access_clause
                AND status = 'A'";
        return $this->retrieve($sql);
    }
    
    /**
     * Filled the ugroups to be notified when admin action is needed
     *
     * @param Integer $groupId
     * @param Array   $ugroups
     *
     * @return Boolean
     */
    public function setMembershipRequestNotificationUGroup($groupId, $ugroups){
        $sql = ' DELETE FROM groups_notif_delegation WHERE group_id ='.$this->da->quoteSmart($groupId);
        if (!$this->update($sql)) {
            return false;
        }
        foreach ($ugroups as $ugroupId) {
            $sql = ' INSERT INTO groups_notif_delegation (group_id, ugroup_id)
                 VALUE ('.$this->da->quoteSmart($groupId).', '.$this->da->quoteSmart($ugroupId).') 
                 ON DUPLICATE KEY UPDATE ugroup_id = '.$this->da->quoteSmart($ugroupId);
            if (!$this->update($sql)) {
                return false;
            }
        }
        return true;
    }

     /**
     * Returns the ugroup to be notified when admin action is needed for given project
     * 
     * @param Integer $groupId
     * 
     * @return DataAccessResult
     */
    public function getMembershipRequestNotificationUGroup($groupId){
        $sql = ' SELECT ugroup_id FROM groups_notif_delegation WHERE group_id = '.$this->da->quoteSmart($groupId);
        return $this->retrieve($sql);
    }

    /**
     * Deletes the ugroup to be notified for given project
     *
     * @param Integer $groupId
     *
     * @return Boolean
     */
    public function deleteMembershipRequestNotificationUGroup($groupId){
        $groupId = $this->da->escapeInt($groupId);
        $sql     = 'DELETE FROM groups_notif_delegation WHERE group_id = '.$groupId;
        return $this->update($sql);
    }

    /**
     * Deletes the message set for a given project
     *
     * @param Integer $groupId
     *
     * @return Boolean
     */
    public function deleteMembershipRequestNotificationMessage($groupId){
        $groupId = $this->da->escapeInt($groupId);
        $sql     = 'DELETE FROM groups_notif_delegation_message WHERE group_id = '.$groupId;
        return $this->update($sql);
    }


    /**
     * Returns the message to be displayed to requester asking access for a given project
     *  
     * @param Integer $groupId
     * 
     * @return DataAccessResult
     */  
    public function getMessageToRequesterForAccessProject($groupId) {
        $sql = 'SELECT msg_to_requester FROM groups_notif_delegation_message WHERE group_id='.$this->da->quoteSmart($groupId);
        return $this->retrieve($sql);
    }

    /**
     * Updates the message to be displayed to requester asking access for a given project
     *  
     * @param Integer $groupId
     * @param String  $message
     */  
    public function setMessageToRequesterForAccessProject($groupId, $message) {
        $sql = 'INSERT INTO groups_notif_delegation_message (group_id, msg_to_requester) VALUES ('.$this->da->quoteSmart($groupId).', '.$this->da->quoteSmart($message).')'.
                ' ON DUPLICATE KEY UPDATE msg_to_requester='.$this->da->quoteSmart($message);
        return $this->update($sql);
    }

    /**
     * Set SVN header
     *
     * @param Integer $groupId
     * @param String  $mailingHeader
     *
     * @return Boolean
     */
    function setSvnHeader($groupId, $mailingHeader) {
        $sql = ' UPDATE groups
                 SET svn_events_mailing_header = '.$this->da->quoteSmart($mailingHeader).'
                 WHERE group_id = '.$this->da->escapeInt($groupId);
        return $this->update($sql);
    }

    public function searchGlobal($words, $offset, $exact) {
        return $this->searchGlobalPaginated($words, $offset, $exact, 26);
    }

    public function searchGlobalPaginated($words, $offset, $exact, $limit) {
        return $this->searchGlobalParams($words, $offset, $exact, '', '', $limit);
    }

    public function searchGlobalForRestrictedUsers($words, $offset, $exact, $user_id) {
        return $this->searchGlobalPaginatedForRestrictedUsers($words, $offset, $exact, $user_id, 26);
    }

    public function searchGlobalPaginatedForRestrictedUsers($words, $offset, $exact, $user_id, $limit) {
        $user_id = $this->da->escapeInt($user_id);
        $from  = " JOIN user_group ON (user_group.group_id = groups.group_id)";
        $where = " AND user_group.user_id = $user_id";
        return $this->searchGlobalParams($words, $offset, $exact, $from, $where, $limit);
    }

    private function searchGlobalParams($words, $offset, $exact, $from = '', $where = '', $limit = 26) {
        $offset = $this->da->escapeInt($offset);
        $limit  = $this->da->escapeInt($limit);
        if ($exact === true) {
            $group_name = $this->searchExactMatch($words);
            $short_desc = $this->searchExactMatch($words);
            $long_desc  = $this->searchExactMatch($words);
        } else {
            $group_name = $this->searchExplodeMatch('group_name', $words);
            $short_desc = $this->searchExplodeMatch('short_description', $words);
            $long_desc  = $this->searchExplodeMatch('unix_group_name', $words);
        }

        $private = $this->da->quoteSmart(Project::ACCESS_PRIVATE);

        $sql = "SELECT DISTINCT group_name, unix_group_name, groups.group_id, short_description
                FROM groups
                    INNER JOIN group_desc_value ON (group_desc_value.group_id = groups.group_id)
                    $from
                WHERE status='A'
                AND access != $private
                AND (
                        (group_name LIKE $group_name)
                     OR (short_description LIKE $short_desc)
                     OR (unix_group_name LIKE $long_desc)
                     OR (group_desc_value.value LIKE $long_desc)
                )
                $where
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function countActiveProjects() {
        $sql = "SELECT count(*) AS nb
                FROM groups
                WHERE status = 'A'
                  AND group_id > 100";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function setIsPrivate($project_id) {
        $access     = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "UPDATE groups SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function setIsPublic($project_id) {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart(Project::ACCESS_PUBLIC);

        $sql = "UPDATE groups SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function setUnrestricted($project_id) {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart(Project::ACCESS_PUBLIC_UNRESTRICTED);

        $sql = "UPDATE groups SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function disableAllowRestrictedForAll() {
        $public       = $this->da->quoteSmart(Project::ACCESS_PUBLIC);
        $unrestricted = $this->da->quoteSmart(Project::ACCESS_PUBLIC_UNRESTRICTED);
        $sql = "UPDATE groups SET access = $public WHERE access = $unrestricted";

        return $this->update($sql);
    }

    public function setTruncatedEmailsUsage($project_id, $usage) {
        $project_id = $this->da->escapeInt($project_id);
        $usage      = $this->da->escapeInt($usage);

        $sql = "UPDATE groups SET truncated_emails = $usage WHERE group_id = $project_id";

        return $this->update($sql);
    }
}