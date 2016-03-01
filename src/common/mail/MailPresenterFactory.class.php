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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class MailPresenterFactory {

    const FLAMING_PARROT_THEME = 'FlamingParrot';

    /**
     * Create a presenter for email account.
     *
     * @return MailRegisterPresenter
     */
    public function createMailAccountPresenter($login, $password, $confirm_hash, $presenter_role, $logo_url) {
        $color_logo = "#000";
        $color_button = "#347DBA";

        $base_url       = get_server_url();
        $this->setColorTheme($color_logo, $color_button);

        $attributes_presenter = array(
            "login"         => $login,
            "password"      => $password,
            "color_logo"    => $color_logo,
            "color_button"  => $color_button,
            "confirm_hash"  => $confirm_hash,
            "base_url"      => $base_url,
            "logo_url"      => $logo_url
        );

        if ($presenter_role === "user") {
            $presenter = $this->createUserEmailPresenter($attributes_presenter);
        } else if ($presenter_role === "admin") {
            $presenter = $this->createAdminEmailPresenter($attributes_presenter);
        } else if ($presenter_role === "admin-notification") {
            $presenter = $this->createAdminNotificationPresenter($attributes_presenter);
        } else {
            $presenter = $this->createApprovalEmailPresenter($attributes_presenter);
        }
        return $presenter;
    }

    /**
     * Create a presenter for email project.
     *
     */
    public function createMailProjectPresenter(Project $project, $logo_url) {
        $color_logo = "#000";

        $this->setColorTheme($color_logo);
        $presenter = $this->createMailProjectRegisterPresenter($project, $color_logo, $logo_url);

        return $presenter;
    }

    /**
     * Create a presenter for email notifiaction project.
     *
     */
    public function createMailProjectNotificationPresenter(Project $project, $logo_url) {
        $color_logo = "#000";
        $color_button = "#347DBA";

        $this->setColorTheme($color_logo, $color_button);
        if ($project->projectsMustBeApprovedByAdmin()) {
            $presenter = $this->createMailProjectNotificationMustBeApprovedPresenter($project, $color_logo, $logo_url, $color_button);
        } else {
            $presenter = $this->createMailProjectRegisterNotificationPresenter($project, $color_logo, $logo_url, $color_button);
        }

        return $presenter;
    }

    /**
     * Create a presenter for admin
     * account register.
     *
     * @return MailRegisterByAdminPresenter
     */
    private function createAdminEmailPresenter(array $attributes_presenter) {
        $login      = $attributes_presenter["login"];
        $password   = $attributes_presenter["password"];

        include($GLOBALS['Language']->getContent('account/new_account_email'));
        $presenter = new MailRegisterByAdminPresenter(
            $attributes_presenter["logo_url"],
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $attributes_presenter["color_logo"],
            $login,
            $section_three,
            $section_after_password,
            $password
        );
        return $presenter;
    }

    /**
     * Create a presenter for user
     * account register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createUserEmailPresenter(array $attributes_presenter) {
        $base_url       = $attributes_presenter["base_url"];
        $login          = $attributes_presenter["login"];
        $confirm_hash   = $attributes_presenter["confirm_hash"];

        include($GLOBALS['Language']->getContent('include/new_user_email'));
        $redirect_url = $base_url ."/account/login.php?confirm_hash=$confirm_hash";

        $presenter = new MailRegisterByUserPresenter(
             $attributes_presenter["logo_url"],
             $title,
             $section_one,
             $section_two,
             $section_after_login,
             $thanks,
             $signature,
             $help,
             $attributes_presenter["color_logo"],
             $login,
             $redirect_url,
             $redirect_button,
             $attributes_presenter["color_button"]
        );
        return $presenter;
    }

    /**
     * Create a presenter for
     * admin notification.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createAdminNotificationPresenter(array $attributes_presenter) {
        $base_url     = $attributes_presenter["base_url"];
        $redirect_url = $base_url ."/admin/approve_pending_users.php?page=pending";

        $presenter = new MailRegisterByAdminNotificationPresenter(
            $attributes_presenter["logo_url"],
            $GLOBALS['Language']->getText('account_register', 'mail_approval_title'),
            $GLOBALS['Language']->getText('account_register', 'mail_approval_section_one', array($GLOBALS['sys_name']), $attributes_presenter["login"]),
            $GLOBALS['Language']->getText('account_register', 'mail_approval_section_two'),
            $GLOBALS['Language']->getText('account_register', 'mail_thanks'),
            $GLOBALS['Language']->getText('account_register', 'mail_signature', array($GLOBALS['sys_name'])),
            $attributes_presenter["color_logo"],
            $redirect_url,
            $GLOBALS['Language']->getText('account_register', 'mail_approval_redirect_button'),
            $attributes_presenter["color_button"],
            $attributes_presenter["login"],
            "."
        );
        return $presenter;
    }

    /**
     * Create a presenter for approval
     * account register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createApprovalEmailPresenter(array $attributes_presenter) {
        $base_url   = $attributes_presenter["base_url"];
        $login      = $attributes_presenter["login"];

        include($GLOBALS['Language']->getContent('admin/new_account_email'));

        $presenter = new MailRegisterByAdminApprovalPresenter(
            $attributes_presenter["logo_url"],
            $title,
            $section_one,
            $section_two,
            '',
            $thanks,
            $signature,
            $help,
            $attributes_presenter["color_logo"],
            $login,
            $section_three
        );
        return $presenter;
    }

    /**
     * Create a presenter for project register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createMailProjectRegisterPresenter(Project $project, $color_logo, $logo_url) {
        $presenter = new MailProjectOneStepRegisterPresenter(
            $project,
            $color_logo,
            $logo_url
        );

        return $presenter;
    }

    /**
     * Create a presenter for project register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createMailProjectRegisterNotificationPresenter(Project $project, $color_logo, $logo_url, $color_button) {
        $presenter = new MailProjectNotificationPresenter(
            $project,
            $color_logo,
            $logo_url,
            $color_button
        );

        return $presenter;
    }

    /**
     * Create a presenter for project register,
     * which must be approved
     *
     * @return MailRegisterByUserPresenter
     */
    private function createMailProjectNotificationMustBeApprovedPresenter(Project $project, $color_logo, $logo_url, $color_button) {
        $presenter = new MailProjectNotificationMustBeApprovedPresenter(
            $project,
            $color_logo,
            $logo_url,
            $color_button
        );

        return $presenter;
    }

    /**
     * Return the color of theme
     * with references parameters
     *
     * @return string
     */
    private function setColorTheme(&$color_logo = null, &$color_button = null) {
        $defaultTheme   = ForgeConfig::get('sys_themedefault');

        if ($this->themeIsFlamingParrot($defaultTheme)) {
            $defaultThemeVariant = ForgeConfig::get('sys_default_theme_variant');
            $color_logo          = FlamingParrot_Theme::getColorOfCurrentTheme($defaultThemeVariant);
            $color_button        = $color_logo;
        }
    }

    /**
     * Check if we need to display the theme color.
     *
     * @return boolean
     */
    private function themeIsFlamingParrot($theme) {
        return $theme === self::FLAMING_PARROT_THEME;
    }
}
