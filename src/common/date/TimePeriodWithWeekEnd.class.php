<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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

/**
 * A time period, starting at a given date, and with a given duration.
 */
class TimePeriodWithWeekEnd extends TimePeriod {
    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    public function getDayOffsets() {
        if ($this->getDuration() < 0) {
            return array(0);
        } else {
            return range(0, $this->getDuration());
        }
    }
}