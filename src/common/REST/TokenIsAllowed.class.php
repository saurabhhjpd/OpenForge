<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

use \Luracast\Restler\RestException;

use Rest_Exception_InvalidTokenException;
use User_LoginException;

class TokenIsAllowed {
    
    /** @var UserManager */
    private $user_manager;

    public function __construct() {
        $this->user_manager = UserManager::build();
    }

    /**
     * @return boolean
     * @throws RestException
     */
    public function isAllowed() {
        try {
            if ($this->requestIsOption() || $this->currentUserIsNotAnonymous()) {
                return true;
            }
        } catch (User_LoginException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (Rest_Exception_InvalidTokenException $exception) {
            throw new RestException(401, $exception->getMessage());
        }

        return false;
    }

    private function requestIsOption() {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS';
    }

    private function currentUserIsNotAnonymous() {
        $user = $this->user_manager->getCurrentUser();
        if ($user && ! $user->isAnonymous()) {
            return true;
        }
    }
}
