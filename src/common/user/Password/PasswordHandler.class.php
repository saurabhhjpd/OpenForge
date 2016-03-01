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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

abstract class PasswordHandler {
    // We need 16 hex chars (8 bytes) to use as a salt to generate the UNIX password
    const SALT_SIZE = 8;

    public abstract function verifyHashPassword($plain_password, $hash_password);

    public abstract function computeHashPassword($plain_password);

    public abstract function isPasswordNeedRehash($hash_password);

    /**
     * Generate Unix shadow password
     *
     * @param String $plain_password Clear password
     *
     * @return String
     */
    public abstract function computeUnixPassword($plain_password);
}