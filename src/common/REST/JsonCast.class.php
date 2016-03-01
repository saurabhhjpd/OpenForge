<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * This class format values for the JSON responses provided by
 * the REST routes
 */

namespace Tuleap\REST;

class JsonCast {

    /**
     * Cast a value to int if it's not null
     * @return int|null
     */
    public static function toInt($value) {
        if (! is_null($value) && $value !== '') {
            return (int)$value;
        }

        return null;
    }

    /**
     * Cast a value to boolean if it's not null
     * @return boolean|null
     */
    public static function toBoolean($value) {
        if (! is_null($value) && $value !== '') {
            return (bool) $value;
        }

        return null;
    }

    /**
     * Cast a value to float if it's not null
     * @return float|null
     */
    public static function toFloat($value) {
        if (! is_null($value) && $value !== '') {
            return floatval($value);
        }

        return null;
    }

    /**
     * Cast a UNIX Timestamp to an ISO formatted date string
     * @return string|null
     */
    public static function toDate($value) {
        if (! is_null($value) && $value !== '') {
            return date('c', $value);
        }

        return null;
    }

}
