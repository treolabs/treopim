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

Espo.define('pim:views/product-image/record/list-in-product', 'pim:views/record/list',
    Dep => Dep.extend({

        actionQuickView: function (data) {
            data = data || {};
            var id = data.id;
            if (!id) return;

            var model = null;
            if (this.collection) {
                model = this.collection.get(id);
            }
            if (!data.scope && !model) {
                return;
            }

            var scope = data.scope || model.name || this.scope;

            var viewName = this.getMetadata().get(['clientDefs', 'Product', 'relationshipPanels', 'productImages', 'modalDetailView'])
                || this.getMetadata().get('clientDefs.' + scope + '.modalViews.detail')
                || 'views/modals/detail';
            var layoutName = this.getMetadata().get(['clientDefs', 'Product', 'relationshipPanels', 'productImages', 'detailLayout']);

            if (!this.quickDetailDisabled) {
                Espo.Ui.notify(this.translate('loading', 'messages'));

                var options = {
                    scope: scope,
                    model: model,
                    id: id,
                    layoutName: layoutName,
                    productId: this.getParentView().model.id,
                    optionsToPass: ['entityId']
                };
                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }
                this.createView('modal', viewName, options, function (view) {
                    this.listenToOnce(view, 'after:render', function () {
                        Espo.Ui.notify(false);
                    });
                    view.render();

                    this.listenToOnce(view, 'remove', function () {
                        this.clearView('modal');
                    }, this);

                    this.listenToOnce(view, 'after:edit-cancel', function () {
                        this.actionQuickView({id: view.model.id, scope: view.model.name});
                    }, this);

                    this.listenToOnce(view, 'after:save', function (model) {
                        this.trigger('after:save', model);
                    }, this);

                    this.listenToOnce(view, 'editFromDetail', function () {
                        this.actionQuickEdit(data);
                    }, this);
                }, this);
            } else {
                this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: true});
            }
        },

        actionQuickEdit: function (data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            var model = null;
            if (this.collection) {
                model = this.collection.get(id);
            }
            if (!data.scope && !model) {
                return;
            }

            var scope = data.scope || model.name || this.scope;

            var viewName = this.getMetadata().get(['clientDefs', 'Product', 'relationshipPanels', 'productImages', 'modalEditView'])
                || this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit')
                || 'views/modals/edit';
            var layoutName = this.getMetadata().get(['clientDefs', 'Product', 'relationshipPanels', 'productImages', 'detailLayout']);

            if (!this.quickEditDisabled) {
                Espo.Ui.notify(this.translate('loading', 'messages'));
                var options = {
                    scope: scope,
                    id: id,
                    model: model,
                    fullFormDisabled: data.noFullForm,
                    layoutName: layoutName,
                    productId: this.getParentView().model.id,
                    returnUrl: this.getRouter().getCurrentUrl(),
                    returnDispatchParams: {
                        controller: scope,
                        action: null,
                        options: {
                            isReturn: true
                        }
                    }
                };
                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }
                this.createView('modal', viewName, options, function (view) {
                    view.once('after:render', function () {
                        Espo.Ui.notify(false);
                    });

                    view.render();

                    this.listenToOnce(view, 'remove', function () {
                        this.clearView('modal');
                    }, this);

                    this.listenToOnce(view, 'after:save', function (m) {
                        var model = this.collection.get(m.id);
                        if (model) {
                            model.set(m.getClonedAttributes());
                        }

                        this.trigger('after:save', m);
                    }, this);
                }, this);
            } else {
                var options = {
                    id: id,
                    model: this.collection.get(id),
                    returnUrl: this.getRouter().getCurrentUrl(),
                    returnDispatchParams: {
                        controller: scope,
                        action: null,
                        options: {
                            isReturn: true
                        }
                    }
                };
                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }
                this.getRouter().navigate('#' + scope + '/edit/' + id, {trigger: false});
                this.getRouter().dispatch(scope, 'edit', options);
            }
        },

    })
);