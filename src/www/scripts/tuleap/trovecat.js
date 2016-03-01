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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

!(function ($) {
    $(document).ready(function(){
        var trove_cat_parent_selectbox;

        trove_cat_parent_selectbox = $('select[name=form_parent]');

        if (trove_cat_parent_selectbox !== undefined) {
            updateCheckboxDisabledProperty(trove_cat_parent_selectbox);

            trove_cat_parent_selectbox.change(function() {
                updateCheckboxDisabledProperty(trove_cat_parent_selectbox);
            });
        }
    });

    function updateCheckboxDisabledProperty(trove_cat_parent_selectbox) {
        var trove_cat_parent_selected,
            mandatory_checkbox;

        trove_cat_parent_selected = trove_cat_parent_selectbox.val();
        mandatory_checkbox        = $('input[name=form_mandatory]');

        if (trove_cat_parent_selected === '0') {
            mandatory_checkbox.prop('disabled', false);
        } else {
            mandatory_checkbox.prop('disabled', true);
            mandatory_checkbox.attr('checked', false);
        }
    }
})(window.jQuery);

