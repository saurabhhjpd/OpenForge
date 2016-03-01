<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ForgeAccess_AdminPresenter {

    /** @var bool */
    public $project_admin_can_choose;

    /** @var int */
    public $nb_restricted_users;

    /** @var string */
    public $current_access_mode;

    /** @var string */
    public $localinc_path;

    /** @var boolean */
    public $access_restricted;

    /** @var boolean */
    public $access_regular;

    /** @var boolean */
    public $access_anonymous;

    /** @var string */
    public $title;

    /** @var string */
    public $btn_submit;

    /** @var string html */
    public $csrf_token;

    /** @var string */
    public $ugroup_authenticated_users = '';

    /** @var string */
    public $ugroup_registered_users = '';

    /** @var string */
    public $ugroup_authenticated_users_placeholder;

    /** @var string */
    public $ugroup_registered_users_placeholder;

    /** @var string */
    public $ugroup_authenticated_users_label;

    /** @var string */
    public $ugroup_registered_users_label;

    /** @var string */
    public $customize_ugroups_label_info;

    /** @var string */
    public $who_can_access;

    /** @var string */
    public $projects_visibility;

    /** @var string */
    public $projects_visibility_label;

    /** @var string */
    public $projects_visibility_help;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $title,
        $localinc_path,
        $current_access_mode,
        $nb_restricted_users,
        $ugroup_authenticated_users,
        $ugroup_registered_users,
        $project_admin_can_choose
    ) {
        $this->title                    = $title;
        $this->csrf_token               = $csrf->fetchHTMLInput();
        $this->localinc_path            = $localinc_path;
        $this->current_access_mode      = $current_access_mode;
        $this->nb_restricted_users      = $nb_restricted_users;
        $this->project_admin_can_choose = $project_admin_can_choose;

        $this->is_localinc_obsolete = $this->isLocalIncObsolete();

        $this->access_anonymous  = ($current_access_mode === ForgeAccess::ANONYMOUS);
        $this->access_regular    = ($current_access_mode === ForgeAccess::REGULAR);
        $this->access_restricted = ($current_access_mode === ForgeAccess::RESTRICTED);

        $this->btn_submit                = $GLOBALS['Language']->getText('admin_main', 'save_conf');
        $this->localinc_obsolete_message = $GLOBALS['Language']->getText(
            'admin_main',
            'localinc_obsolete_message',
            $this->localinc_path
        );

        $this->access_anonymous_label  = $GLOBALS['Language']->getText('admin_main', 'access_anonymous_label');
        $this->access_anonymous_desc   = $GLOBALS['Language']->getText('admin_main', 'access_anonymous_desc');
        $this->access_regular_label    = $GLOBALS['Language']->getText('admin_main', 'access_regular_label');
        $this->access_regular_desc     = $GLOBALS['Language']->getText('admin_main', 'access_regular_desc');
        $this->access_restricted_label = $GLOBALS['Language']->getText('admin_main', 'access_restrited_label');
        $this->access_restricted_desc  = $GLOBALS['Language']->getText('admin_main', 'access_restrited_desc');
        $this->current_restricted_users_message = $GLOBALS['Language']->getText(
            'admin_main',
            'current_restricted_users_message',
            $this->nb_restricted_users
        );

        if ($ugroup_authenticated_users != false) {
            $this->ugroup_authenticated_users = $ugroup_authenticated_users;
        }
        if ($ugroup_registered_users != false) {
            $this->ugroup_registered_users = $ugroup_registered_users;
        }
        $this->ugroup_authenticated_users_placeholder = $GLOBALS['Language']->getText('admin_main', 'ugroup_authenticated_users_placeholder', array(ForgeConfig::get('sys_org_name')));
        $this->ugroup_registered_users_placeholder    = $GLOBALS['Language']->getText('admin_main', 'ugroup_registered_users_placeholder', array(ForgeConfig::get('sys_org_name')));

        $this->ugroup_authenticated_users_label       = $GLOBALS['Language']->getText('admin_main', 'ugroup_authenticated_users_label');
        $this->ugroup_registered_users_label          = $GLOBALS['Language']->getText('admin_main', 'ugroup_registered_users_label');

        $this->customize_ugroups_label_info           = $GLOBALS['Language']->getText('admin_main', 'customize_ugroups_label_info');

        $this->who_can_access            = $GLOBALS['Language']->getText('admin_main', 'who_can_access');
        $this->projects_visibility       = $GLOBALS['Language']->getText('admin_main', 'projects_visibility');
        $this->projects_visibility_label = $GLOBALS['Language']->getText('admin_main', 'projects_visibility_label');
        $this->projects_visibility_help  = $GLOBALS['Language']->getText('admin_main', 'projects_visibility_help');
    }

    private function isLocalIncObsolete() {
        include($this->localinc_path);
        $variables_in_local_inc = get_defined_vars();

        return isset($variables_in_local_inc['sys_allow_anon'])
            || isset($variables_in_local_inc['sys_allow_restricted_users']);
    }

}