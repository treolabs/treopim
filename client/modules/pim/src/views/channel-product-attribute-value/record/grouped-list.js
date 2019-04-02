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

Espo.define('pim:views/channel-product-attribute-value/record/grouped-list', 'views/record/list',
    Dep => Dep.extend({

        selectable: false,

        checkboxes: false,

        massActionsDisabled: true,

        checkAllResultDisabled: true,

        buttonsDisabled: true,

        paginationEnabled: false,

        showCount: false,

        rowHasOwnLayout: true,

        showMore: false,

        createAction: null,

        actionList: null,

        template: 'pim:channel-product-attribute-value/record/grouped-list',

        setup() {
            Dep.prototype.setup.call(this);

            this.createAction = this.options.create || this.createAction;

            if (this.options.select) {
                this.actionList = [{
                    data: {
                        action: 'selectChannelAttributes',
                        channelId: this.options.channelId
                    },
                    label: this.translate('Select')
                }];
            }
        },

        data() {
            return {
                collectionLabel: this.options.collectionLabel,
                channelId: this.options.channelId,
                createAction: this.createAction,
                actionList: this.actionList,
                ...Dep.prototype.data.call(this)
            };
        },

        getInternalLayoutForModel: function (callback, model) {
            this._internalLayout = this._convertLayout(this.listLayout);
            callback(this._convertLayout(this.listLayout, model));
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

            var viewName = 'pim:views/channel-product-attribute-value/modals/grouped-edit';

            Espo.Ui.notify(this.translate('loading', 'messages'));
            this.createView('modal', viewName, {
                scope: scope,
                id: id,
                model: model,
            }, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', function () {
                    this.clearView('modal');
                }, this);

            }, this);
        },

        actionQuickRemove: function (data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            var model = this.collection.get(id);
            if (!this.getAcl().checkModel(model, 'delete')) {
                this.notify('Access denied', 'error');
                return false;
            }

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                this.collection.trigger('model-removing', id);
                this.collection.remove(model);
                this.notify('Removing...');
                model.destroy({
                    wait: true,
                    success: function () {
                        this.collection.trigger('after:remove');
                        this.notify('Removed', 'success');
                        this.removeRecordFromList(id);
                        this.getParentView().actionRefresh();
                    }.bind(this),
                    error: function() {
                        this.notify('Error occured', 'error');
                        this.collection.push(model);
                    }.bind(this),
                });
            }, this);
        },

    })
);

