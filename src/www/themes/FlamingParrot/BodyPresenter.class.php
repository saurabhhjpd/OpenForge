<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class FlamingParrot_BodyPresenter {

    /** @var string */
    private $nav;

    /** @var string */
    private $request;

    /** @var string */
    private $title;

    /** @var string */
    private $img_root;

    /** @var string or boolean */
    private $selected_top_tab;

    /** @var string */
    private $notifications_placeholder;

    /** @var array */
    private $body_class;

    function __construct(
        $request,
        $title,
        $img_root,
        $selected_top_tab,
        $notifications_placeholder,
        $body_class
    ) {
        $this->request                   = $request;
        $this->title                     = $title;
        $this->img_root                  = $img_root;
        $this->selected_top_tab          = $selected_top_tab;
        $this->notifications_placeholder = $notifications_placeholder;
        $this->body_class                = $body_class;
    }

    public function notificationsPlaceholder() {
        return $this->notifications_placeholder;
    }

    public function body_class() {
        return $this->body_class;
    }

}

?>
