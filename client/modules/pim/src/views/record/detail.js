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

Espo.define('pim:views/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            this.bottomView = this.getMetadata().get(`clientDefs.${this.scope}.bottomView.${this.type}`) || this.bottomView;

            Dep.prototype.setup.call(this);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            let parentView = this.getParentView();
            if (parentView.options.params && parentView.options.params.setEditMode) {
                this.actionEdit();
            }
        },

    })
);

