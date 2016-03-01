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

class Account_RegisterPresenter {

    public $prefill_values;
    public $login;
    public $email;
    public $email_tooltip;
    public $realname;
    public $siteupdate;
    public $agreeupdate;
    public $purpose;
    public $purpose_directions;
    public $password_robustness;
    public $good;
    public $bad;
    public $new_password;
    public $timezone_selector;
    public $should_display_purpose;
    public $json_password_strategy_keys;
    public $password_strategy_validators;
    public $legal = '';

    public function __construct(Account_RegisterPrefillValuesPresenter $prefill_values) {
        $this->prefill_values         = $prefill_values;
        $this->login                  = $GLOBALS['Language']->getText('account_register', 'login');
        $this->email                  = $GLOBALS['Language']->getText('account_register', 'email');
        $this->email_tooltip          = $GLOBALS['Language']->getText('account_register', 'email_tooltip');
        $this->realname               = $GLOBALS['Language']->getText('account_register', 'realname');
        $this->siteupdate             = $GLOBALS['Language']->getText('account_register', 'siteupdate');
        $this->agreeupdate             = $GLOBALS['Language']->getText('account_register', 'agreeupdate');
        $this->purpose                = $GLOBALS['Language']->getText('account_register', 'purpose');
        $this->purpose_directions     = $GLOBALS['Language']->getText('account_register', 'purpose_directions');
        $this->password_robustness    = $GLOBALS['Language']->getText('account_check_pw', 'password_robustness');
        $this->good                   = $GLOBALS['Language']->getText('account_check_pw', 'good');
        $this->bad                    = $GLOBALS['Language']->getText('account_check_pw', 'bad');
        $this->new_password           = $GLOBALS['Language']->getText('account_change_pw', 'new_password');
        $this->timezone_selector      = new Account_TimezoneSelectorPresenter($this->prefill_values->form_timezone);
        $this->should_display_purpose = $GLOBALS['sys_user_approval'] == 1;

        $password_strategy = new PasswordStrategy();
        include($GLOBALS['Language']->getContent('account/password_strategy'));
        $this->json_password_strategy_keys = json_encode(array_keys($password_strategy->validators));
        $this->password_strategy_validators = array();
        foreach($password_strategy->validators as $key => $v) {
            $this->password_strategy_validators[] = array(
                'key'         => $key,
                'description' => $v->description()
            );
        }
    }
}
