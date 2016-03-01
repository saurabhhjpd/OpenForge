<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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

namespace Tuleap\REST;

use User_LoginManager;
use User_PasswordExpirationChecker;
use EventManager;
use Tuleap\REST\Exceptions\NoAuthenticationHeadersException;
use Rest_TokenDao;
use Rest_TokenManager;
use Rest_TokenFactory;
use Rest_Token;
use PasswordHandlerFactory;

class UserManager {

    /** @var \UserManager */
    private $user_manager;

    /** @var User_LoginManager */
    private $login_manager;

    const HTTP_TOKEN_HEADER     = 'X-Auth-Token';
    const PHP_HTTP_TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    const HTTP_USER_HEADER      = 'X-Auth-UserId';
    const PHP_HTTP_USER_HEADER  = 'HTTP_X_AUTH_USERID';


    public function __construct(\UserManager $user_manager, User_LoginManager $login_manager) {
        $this->user_manager  = $user_manager;
        $this->login_manager = $login_manager;
    }

    public static function build() {
        $self = __CLASS__;
        $user_manager = \UserManager::instance();
        return new $self(
            $user_manager,
            new User_LoginManager(
                EventManager::instance(),
                $user_manager,
                new User_PasswordExpirationChecker(),
                PasswordHandlerFactory::getPasswordHandler()
            )
        );
    }

    /**
     * Return user of current request in REST context
     *
     * Tries to get authenticcation scheme from cookie if any, fallback on token
     * authentication
     *
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusSuspendedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_PasswordExpiredException
     *
     * @return \PFUser
     */
    public function getCurrentUser() {
        try {
            $user = $this->getUserFromCookie();
            if ($user->isAnonymous()) {
                $user = $this->getUserFromToken();
                $this->login_manager->validateAndSetCurrentUser($user);
            }
            return $user;
        } catch (NoAuthenticationHeadersException $exception) {
            return $this->user_manager->getUserAnonymous();
        }
    }

    /**
     * We need it to browse the API as we are logged in through the Web UI
     * @throws \User_PasswordExpiredException
     */
    private function getUserFromCookie() {
        $current_user = $this->user_manager->getCurrentUser();
        if (! $current_user->isAnonymous()) {
            $password_expiration_checker = new User_PasswordExpirationChecker();
            $password_expiration_checker->checkPasswordLifetime($current_user);
        }
        return $current_user;
    }

    /**
     * @return PFUser
     * @throws NoAuthenticationHeadersException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromToken() {
        if (! isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        if (! isset($_SERVER[self::PHP_HTTP_USER_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        $token = new Rest_Token(
            $_SERVER[self::PHP_HTTP_USER_HEADER],
            $_SERVER[self::PHP_HTTP_TOKEN_HEADER]
        );

        $token_dao = new Rest_TokenDao();
        $token_manager = new Rest_TokenManager(
            $token_dao,
            new Rest_TokenFactory($token_dao),
            $this->user_manager
        );
        return $token_manager->checkToken($token);
    }
}
