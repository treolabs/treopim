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

Espo.define('pim:views/product-type-package/grid', 'views/base',
    Dep => Dep.extend({

        template: 'pim:product-type-package/grid',

        mode: 'detail',

        layoutFields: ['measuringUnit', 'content', 'basicUnit', 'packingUnit'],

        data() {
            return {
                layoutFields: this.layoutFields
            };
        },

        afterRender() {
            this.buildGrid();

            Dep.prototype.afterRender.call(this);
        },

        buildGrid() {
            if (this.nestedViews) {
                for (let child in this.nestedViews) {
                    this.clearView(child);
                }
            }

            let mode = this.getDetailViewMode();

            this.layoutFields.forEach(field => {
                let viewName = this.model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(this.model.getFieldType(field));
                this.createView(field, viewName, {
                    mode: mode,
                    inlineEditDisabled: true,
                    model: this.model,
                    el: this.options.el + ` .field[data-name="${field}"]`,
                    defs: {
                        name: field,
                    }
                }, view => view.render());
            });
        },

        getDetailViewMode() {
            let mode = 'detail';
            let parentView = this.getParentView();
            if (parentView) {
                let detailView = this.getParentView().getDetailView();
                if (detailView) {
                    mode = detailView.mode;
                }
            }
            return mode;
        }

    })
);
