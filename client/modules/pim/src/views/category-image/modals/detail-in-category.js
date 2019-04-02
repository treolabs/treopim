/*
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

Espo.define('pim:views/category-image/modals/detail-in-category', 'views/modals/detail',
    Dep => Dep.extend({

        fullFormDisabled: true,

        sideDisabled: true,

        detailView: 'pim:views/category-image/record/detail-in-category',

        actionEdit: function () {
            this.trigger('editFromDetail');
            this.trigger('leave');
            this.dialog.close();
        },

        createRecordView: function (callback) {
            this.model.categoryId = this.options.categoryId;

            Dep.prototype.createRecordView.call(this, callback);
        },

    })
);