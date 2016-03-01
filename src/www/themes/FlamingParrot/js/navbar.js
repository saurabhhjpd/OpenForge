/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * Handle navbar events
 */
!function($) {
    function initCustomScrollbar() {
        $('.projects-nav .dropdown-menu').jScrollPane({
            autoReinitialise: true,
            hideFocus: true,
            verticalGutter: 0
        }).bind('mousewheel', function(e) {
            e.preventDefault();
        });
    }

    $(document).ready(function() {
        var input_filter          = $('#filter-projects');
        var list_element_selector = '.projects-nav .dropdown-menu li.project';
        var filter                = new tuleap.core.listFilter();

        filter.init(input_filter, list_element_selector);

        $('.projects-nav').click(function(event) {
            if (! $(this).hasClass('open')) {
                input_filter.focus();
                initCustomScrollbar();
            }
        });
    });
}(window.jQuery);