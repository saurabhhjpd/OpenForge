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

namespace Tuleap\REST\v1;

class BacklogItemRepresentationBase {

    const BACKLOG_ROUTE = 'backlog';

    const CONTENT_ROUTE = 'content';

    const ROUTE         = 'backlog_items';

    /**
     * @var Int
     */
    public $id;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $type;

    /**
     * @var String
     */
    public $short_type;

    /**
     * @var String
     */
    public $status;

    /**
     * @var String
     */
    public $color;

    /**
     * @var Float
     */
    public $initial_effort;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var \Tuleap\REST\v1\BacklogItemParentReferenceBase
     */
    public $parent;

    /**
     * @var \Tuleap\Project\REST\ProjectReference
     */
    public $project;

    /**
     * @var Boolean
     */
    public $has_children;

    /**
     * @var array
     */
    public $accept;
}
