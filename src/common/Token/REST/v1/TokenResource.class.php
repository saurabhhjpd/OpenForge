<?php
/**
 * Copyright (c) Enalean, 2013-2015. All Rights Reserved.
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

namespace Tuleap\Token\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Token\REST\TokenRepresentation;
use Tuleap\REST\Header;
use Exception;
use UserManager;
use EventManager;
use User_LoginManager;
use User_InvalidPasswordWithUserException;
use User_InvalidPasswordException;
use User_PasswordExpirationChecker;
use PasswordHandlerFactory;

/**
 * Wrapper for token related REST methods
 */
class TokenResource {

    /** @var UserManager */
    private $user_manager;

    public function __construct() {
        $this->user_manager = UserManager::instance();
    }

    /**
     * Generate a token
     *
     * Generate a token for authentication for the current user
     *
     * @url POST
     *
     * @throws 400
     * @throws 500
     *
     * @param string $username The username of the user
     * @param string $password The password of the user
     *
     * @return Tuleap\Token\REST\TokenRepresentation
     */
    public function post($username, $password) {
        try {
            $user_login = new User_LoginManager(
                EventManager::instance(),
                $this->user_manager,
                new User_PasswordExpirationChecker(),
                PasswordHandlerFactory::getPasswordHandler()
            );

            $user  = $user_login->authenticate($username, $password);
            $this->sendAllowHeaders();

            $token = new TokenRepresentation();
            $token->build(
                $this->getTokenManager()->generateTokenForUser($user)
            );
            return $token;
        } catch(User_LoginException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch(User_InvalidPasswordWithUserException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch(User_InvalidPasswordException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch(Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * Expire a token
     *
     * Expire a given token of the current user
     *
     * @url DELETE {id}
     *
     * @throws 500
     *
     * @param string $id Id of the token
     */
    protected function delete($id) {
        $this->sendAllowHeadersForToken();
        try {
            $this->getTokenManager()->expireToken(
                new \Rest_Token(
                    $this->user_manager->getCurrentUser()->getId(),
                    $id
                )
            );
        } catch (Rest_Exception_InvalidTokenException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch(Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * Expire all tokens
     *
     * Expire all tokens of the current user
     *
     * @url DELETE
     */
    protected function deleteAll() {
        $this->sendAllowHeaders();
        $this->getTokenManager()->expireAllTokensForUser(
            $this->user_manager->getCurrentUser()
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        $this->sendAllowHeaders();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the token
     */
    public function optionsForToken($id) {
        $this->sendAllowHeadersForToken();
    }

    private function getTokenManager() {
        $token_dao = new \Rest_TokenDao();
        return new \Rest_TokenManager(
            $token_dao,
            new \Rest_TokenFactory($token_dao),
            $this->user_manager
        );
    }

    private function sendAllowHeaders() {
        Header::allowOptionsPostDelete();
    }

    private function sendAllowHeadersForToken() {
        Header::allowOptionsDelete();
    }
}
