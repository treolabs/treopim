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

Espo.define('pim:views/product/record/panels/channel-products', 'views/record/panels/bottom',
    Dep => Dep.extend({

        template: 'record/panels/relationship',

        channels: [],

        setup() {
            Dep.prototype.__proto__.setup.call(this);

            this.listenTo(this.model, 'after:relate after:unrelate', data => {
                if (data === 'categories') {
                    this.actionRefresh();
                }
            }, this);

            this.once('after:render', () => {
                this.setupList();
            });
        },

        setupList() {
            let layoutName = 'listSmall';
            let listLayout = null;
            let layout = this.defs.layout || null;
            if (layout) {
                if (typeof layout == 'string') {
                    layoutName = layout;
                } else {
                    layoutName = 'listRelationshipCustom';
                    listLayout = layout;
                }
            }

            this.channels = [];

            let promise = this.ajaxGetRequest(`Markets/Product/${this.model.id}/channels`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                let formedResponse = response.map(item => {
                    return {
                        id: item.channelId,
                        name: item.channelName,
                        code: item.channelCode,
                        isActive: item.channelIsActive,
                        catalogId: item.catalogId,
                        catalogName: item.catalogName,
                        category: item.categoryName
                    };
                });

                this.channels = formedResponse;

                this.getCollectionFactory().create('Channel', collection => {
                    collection.total = formedResponse.length;

                    formedResponse.forEach(product => {
                        this.getModelFactory().create('Channel', model => {
                            model.set(product);
                            model.id = product.id;
                            collection.add(model);
                            collection._byId[model.id] = model;
                        });
                    }, this);

                    let viewName = this.defs.recordListView || this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'views/record/list';

                    this.createView('list', viewName, {
                        collection: collection,
                        el: `${this.options.el} .list-container`,
                        type: 'list',
                        searchManager: this.searchManager,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: true,
                        buttonsDisabled: true,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsView: false,
                        layoutName: layoutName,
                        listLayout: listLayout,
                    }, function (view) {
                        view.render();
                    }, this);
                });
            });
        },

        actionRefresh: function () {
            this.setupList();
        },

        showEmptyData() {
            this.$el.find('.list-container').html(this.translate('No Data'));
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },
    })
);