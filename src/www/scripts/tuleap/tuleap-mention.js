/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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
 * Handle @user
 */
(function ($) {
    tuleap.mention = {
        init: function(selector) {
            $(selector).atwho({
                at: '@',
                tpl: '<li data-value="${atwho-at}${username}"><img class="user-avatar" src="${avatar_url}"> ${real_name} (${username})</li>',
                callbacks: {
                    remote_filter: function(query, callback) {
                        if (query.length > 2) {
                            $.getJSON("/api/v1/users", {query: query}, function(data) {
                                callback(data);
                            });
                        }
                    },
                    sorter: function(query, items, search_key) {
                        if (!query) {
                            return items;
                        }

                        return items.sort(function(a, b) {
                            return a.atwho_order - b.atwho_order;
                        });
                    }
                }
            });

            return this;
        }
    };

    $(document).ready(function () {
        tuleap.mention.init('input[type="text"].user-mention, textarea.user-mention');
    });
})(jQuery);
