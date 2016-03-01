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

    $(function () {
        var form = $('#admin-anonymous');
        if (form.length === 0) {
            return;
        }

        var current_access_mode = form.attr('data-current-access-mode'),
            nb_restricted_users = form.attr('data-nb-restricted-users');

        form.find('[name=access_mode]').click(function () {
            enableSubmitButton();

            if (current_access_mode === 'restricted' && nb_restricted_users > 0) {
                if ($(this).val() !== current_access_mode) {
                    $('#submit-panel').addClass('alert alert-warning');
                } else {
                    $('#submit-panel').removeClass('alert alert-warning');
                }
            }

            if ($(this).val() === 'restricted') {
                $('#customize-ugroup-labels').show();
            } else {
                $('#customize-ugroup-labels').hide();
            }
        });

        form.find('[name=project_admin_can_choose_visibility]').click(function () {
            enableSubmitButton();
        });

        form.find('[type=text]').keydown(function () {
            enableSubmitButton();
        });

        if (current_access_mode === 'restricted') {
            $('#customize-ugroup-labels').show();
        }

        function enableSubmitButton() {
            form.find('[type=submit]').prop('disabled', false);
        }
    });

})(window.jQuery);
