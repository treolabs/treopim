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

Espo.define('pim:views/product-attribute-value/modals/detail', 'views/modals/detail',
    Dep => Dep.extend({

        fullFormDisabled: true,

        actionEdit() {
            const viewName = this.getMetadata().get(['clientDefs', this.scope, 'modalViews', 'edit']) || 'views/modals/edit';
            const options = {
                scope: this.scope,
                id: this.id,
                fullFormDisabled: this.fullFormDisabled
            };

            this.handleRecordViewOptions(options);

            this.createView('quickEdit', viewName, options, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                    this.dialog.hide();
                }, this);

                this.listenToOnce(view, 'remove', function () {
                    this.dialog.show();
                }, this);

                this.listenToOnce(view, 'leave', function () {
                    this.remove();
                }, this);

                this.listenToOnce(view, 'after:save', function (model) {
                    this.trigger('after:save', model);

                    this.model.set(model.getClonedAttributes());
                }, this);

                view.render();
            }, this);
        },

        handleRecordViewOptions(options) {
            _.extend(options, {
                model: this.model
            });
        }
    })
);