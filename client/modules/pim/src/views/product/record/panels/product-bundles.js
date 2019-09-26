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

Espo.define('pim:views/product/record/panels/product-bundles', ['views/record/panels/relationship', 'views/record/panels/bottom'],
    (Dep, BottomPanel) => Dep.extend({

        products: [],

        linkScope: 'ProductTypeBundle',

        boolFilterData: {
            notEntity() {
                return this.model.id;
            },
            notBundledProducts() {
                return this.model.id;
            }
        },

        setup() {
            let bottomPanel = new BottomPanel();
            bottomPanel.setup.call(this);

            this.link = this.link || this.defs.link || this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.title || this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            if (!this.getConfig().get('scopeColorsDisabled')) {
                var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
                if (iconHtml) {
                    if (this.defs.label) {
                        this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.scope);
                    } else {
                        this.titleHtml = iconHtml + this.title;
                    }
                }
            }

            var url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!this.readOnly && !this.defs.readOnly) {
                if (!('create' in this.defs)) {
                    this.defs.create = true;
                }
                if (!('select' in this.defs)) {
                    this.defs.select = true;
                }
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            if (this.getAcl().check('Product', 'edit')) {
                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: {
                        link: this.link,
                        scope: 'Product',
                        afterSelectCallback: 'actionCreateLink',
                        boolFilterListCallback: 'getSelectBoolFilterList',
                        boolFilterDataCallback: 'getSelectBoolFilterData',
                        primaryFilterName: this.defs.selectPrimaryFilterName || null
                    }
                });
            }

            this.once('after:render', () => {
                this.setupList();
            });

            this.setupFilterActions();
        },

        setupList() {
            let listLayout = [
                {
                    name: 'name',
                    link: true,
                    notSortable: true,
                },
                {
                    name: 'sku',
                    notSortable: true,
                },
                {
                    name: 'amount',
                    notSortable: true,
                    type: 'float'
                }
            ];

            this.products = [];

            let promise = this.ajaxGetRequest(`Markets/${this.linkScope}/${this.model.id}/bundleProduct`);

            promise.then(response => {
                this.clearNestedViews();
                if (!response.length) {
                    this.showEmptyData();
                    return;
                }

                let formedResponse = response.map(item => {
                    return {
                        id: item.productId,
                        name: item.productName,
                        sku: item.productSku,
                        productTypeBundleId: item.productTypeBundleId,
                        amount: item.amount
                    };
                });

                this.products = formedResponse;

                this.getCollectionFactory().create('Product', collection => {
                    collection.total = formedResponse.length;

                    formedResponse.forEach(product => {
                        this.getModelFactory().create('Product', model => {
                            model.set(product);
                            model.id = product.id;
                            collection.add(model);
                            collection._byId[model.id] = model;
                        });
                    }, this);

                    this.createView('list', 'views/record/list', {
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
                        rowActionsView: 'pim:views/product/record/row-actions/custom-edit-and-unlink',
                        listLayout: listLayout,
                    }, function (view) {
                        view.render();
                    }, this);
                });
            });
        },

        actionCreateLink(models) {
            let items = Array.isArray(models) ? models : [models];
            Promise.all(items.map(item => this.ajaxPostRequest(`${this.linkScope}/action/create`, {
                bundleProductId: this.model.id,
                productId: item.id,
                amount: 1
            }))).then(() => {
                this.notify('Linked', 'success');
                this.setupList();
            });
        },

        actionUnlinkRelated(data) {
            let product = this.products.find(item => item.id === data.id);

            if (!product) {
                return;
            }

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `Markets/${this.linkScope}/${product.productTypeBundleId}/delete`,
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.setupList();
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        actionCustomEdit(data) {
            let product = this.products.find(item => item.id === data.id);

            if (!product) {
                return;
            }

            this.notify('Loading...');

            this.getModelFactory().create(this.linkScope, model => {
                model.id = product.productTypeBundleId;
                model.fetch();
                this.listenToOnce(model, 'sync', function () {
                    let viewName = 'pim:views/modals/edit-without-side';
                    let header = this.getLanguage().translate('Edit') + ': ' + this.getLanguage().translate('Bundle Item', 'labels', this.linkScope)
                    this.createView('modal', viewName, {
                        scope: model.scope,
                        id: product.productTypeBundleId,
                        model: model,
                        sideDisabled: true,
                        header: header
                    }, function (view) {
                        view.once('after:render', function () {
                            Espo.Ui.notify(false);
                        });

                        view.render();

                        this.listenToOnce(view, 'remove', function () {
                            this.clearView('modal');
                        }, this);

                        this.listenToOnce(view, 'after:save', function () {
                            this.setupList();
                        }, this);

                    }, this);
                });
            });
        },

        showEmptyData() {
            this.$el.find('.list-container').html(this.translate('No Data'));
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        actionRefresh: function () {
            this.setupList();
        },

    })
);