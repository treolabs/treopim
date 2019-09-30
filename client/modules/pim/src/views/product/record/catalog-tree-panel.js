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

Espo.define('pim:views/product/record/catalog-tree-panel', 'view',
    Dep => Dep.extend({

        template: 'pim:product/record/catalog-tree-panel',

        catalogs: [],

        categories: [],

        rootCategoriesIds: [],

        catalogTreeData: null,

        events: {
            'click .category-buttons button[data-action="selectAll"]': function (e) {
                this.selectCategoryButtonApplyFilter($(e.currentTarget), {type: 'anyOf'});
            },
            'click .category-buttons button[data-action="selectWithoutCategory"]': function (e) {
                this.selectCategoryButtonApplyFilter($(e.currentTarget), {type: 'isEmpty'});
            },
            'click button[data-action="collapsePanel"]': function () {
                this.actionCollapsePanel();
            }
        },

        data() {
            return {
                scope: this.scope,
                catalogDataList: this.getCatalogDataList()
            }
        },

        setup() {
            this.scope = this.options.scope || this.scope;

            this.wait(true);
            this.getFullEntity('Catalog', {select: 'name,categoriesIds,categoriesNames'}, catalogs => {
                this.catalogs = catalogs;
                this.rootCategoriesIds = this.getRootCategoriesIds();
                this.getFullEntity('Category', {
                    select: 'name,categoryParentId',
                    where: [
                        {
                            type: 'or',
                            value: [
                                {
                                    type: 'in',
                                    attribute: 'id',
                                    value: this.rootCategoriesIds
                                },
                                {
                                    type: 'in',
                                    attribute: 'categoryParentId',
                                    value: this.rootCategoriesIds
                                }
                            ]
                        }
                    ]
                }, categories => {
                    this.categories = categories;
                    this.setupPanels();
                    if (this.model) {
                        this.expandTreeWithProductCategory();
                    }
                    this.wait(false);
                });
            });

            this.listenTo(this, 'resetFilters', () => {
                this.catalogTreeData = null;
                this.selectCategoryButtonApplyFilter(this.$el.find('button[data-action="selectAll"]'), false);
            });

            if (this.model) {
                this.listenTo(Backbone, 'menu-expanded', () => {
                    this.actionCollapsePanel(true);
                });
            }
        },

        expandTreeWithProductCategory() {
            this.ajaxGetRequest(`Product/${this.model.id}/productCategories`).then(productCategories => {
                let id = (((productCategories.list || [])[0] || {}).categoryId);
                if (id) {
                    this.ajaxGetRequest(`Category/${id}`).then(category => {
                        let parentCategoryId = !category.categoryParentId ? id : (category.categoryRoute || '').split('|').find(element => element);
                        let catalog = this.catalogs.find(catalog => (catalog.categoriesIds || []).includes(parentCategoryId));
                        if (catalog) {
                            let catalogTree = this.getView(`category-tree-${catalog.id}`);
                            if (catalogTree && typeof catalogTree.expandCategoryHandler === 'function') {
                                catalogTree.expandCategoryHandler(category);
                            }
                        }
                    });
                }
            });
        },

        getFullEntity(url, params, callback, container) {
            if (url) {
                container = container || [];

                let options = params || {};
                options.maxSize = options.maxSize || 200;
                options.offset = options.offset || 0;

                this.ajaxGetRequest(url, options).then(response => {
                    container = container.concat(response.list || []);
                    options.offset = container.length;
                    if (response.total > container.length || response.total === -1) {
                        this.getFullEntity(url, options, callback, container);
                    } else {
                        callback(container);
                    }
                });
            }
        },

        getRootCategoriesIds() {
            let categories = [];
            this.catalogs.forEach(catalog => {
                if (catalog.categoriesIds) {
                    catalog.categoriesIds.forEach(id => {
                        if (!categories.includes(id)) {
                            categories.push(id);
                        }
                    });
                }
            });
            return categories;
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if ($(window).width() <= 767 || !!this.getStorage().get('catalog-tree-panel', this.scope)) {
                this.actionCollapsePanel();
            }

            if (this.model && !this.getStorage().get('catalog-tree-panel', this.scope)) {
                Backbone.trigger('tree-panel-expanded');
            }
        },

        selectCategoryButtonApplyFilter(button, filterParams) {
            this.selectCategoryButton(button);
            if ($(window).width() <= 767) {
                this.actionCollapsePanel(true);
            }
            if (filterParams) {
                this.applyCategoryFilter(filterParams.type);
            }
        },

        setupPanels() {
            this.createView('categorySearch', 'pim:views/product/record/catalog-tree-panel/category-search', {
                el: '.catalog-tree-panel > .category-panel > .category-search',
                scope: this.scope,
                catalogs: this.catalogs
            }, view => {
                view.render();
                this.listenTo(view, 'category-search-select', category => {
                    let categoryTree = this.getView(`category-tree-${category.catalogId}`);
                    if (categoryTree) {
                        categoryTree.expandCategoryHandler(category);
                    }
                });
            });

            this.catalogs.forEach(catalog => {
                if (catalog.categoriesIds && catalog.categoriesIds.length) {
                    this.createView(`category-tree-${catalog.id}`, 'pim:views/product/record/catalog-tree-panel/category-tree', {
                        name: catalog.id,
                        el: `${this.options.el} > .category-panel > .category-tree > .panel[data-name="${catalog.id}"]`,
                        scope: this.scope,
                        catalog: catalog,
                        categories: this.categories
                    }, view => {
                        view.listenTo(view, 'category-tree-select', category => {
                            this.selectCategory(category);
                        });
                        view.render();
                    });
                }
            });
        },

        selectCategory(category) {
            if (category && category.id && category.catalogId) {
                if ($(window).width() <= 767) {
                    this.actionCollapsePanel();
                }
                this.applyCategoryFilter('anyOf', category);
            }
        },

        applyCategoryFilter(type, category) {
            this.catalogTreeData = {};
            if (type === 'isEmpty') {
                this.catalogTreeData.advanced = {
                    productCategories: {
                        type: 'isNotLinked',
                        data: {
                            type: type
                        },
                        hidden: true
                    }
                };
            } else if (type === 'anyOf' && category) {
                this.catalogTreeData.bool = {
                    linkedWithCategory: true
                };
                this.catalogTreeData.boolData = {
                    linkedWithCategory: category.id
                };
                this.catalogTreeData.advanced = {
                    catalog: {
                        type: 'equals',
                        field: 'catalogId',
                        value: category.catalogId,
                        data: {
                            type: 'is',
                            idValue: category.catalogId,
                            nameValue: (this.catalogs.find(catalog => catalog.id === category.catalogId) || {}).name
                        },
                        hidden: true
                    }
                };
            }
            this.trigger('select-category', this.catalogTreeData);
        },

        selectCategoryButton(button) {
            this.$el.find('.panel-collapse.collapse[class^="catalog-"].in').collapse('hide');
            this.$el.find('ul.list-group-tree li.child').removeClass('active');
            this.$el.find('.category-buttons > button').removeClass('active');
            button.addClass('active');
        },

        actionCollapsePanel(forceHide) {
            let categoryPanel = this.$el.find('.category-panel');
            if (categoryPanel.hasClass('hidden') && !forceHide) {
                categoryPanel.removeClass('hidden');
                this.showUtilityElements();
                this.getStorage().set('catalog-tree-panel', this.scope, '');
                if (this.model) {
                    Backbone.trigger('tree-panel-expanded');
                }
            } else {
                categoryPanel.addClass('hidden');
                this.hideUtilityElements();
                this.getStorage().set('catalog-tree-panel', this.scope, 'collapsed');
            }
            $(window).trigger('resize');
        },

        showUtilityElements() {
            let button = this.$el.find('button[data-action="collapsePanel"]');
            button.removeClass('collapsed');
            button.find('span.toggle-icon-left').removeClass('hidden');
            button.find('span.toggle-icon-right').addClass('hidden');

            this.$el.removeClass('catalog-tree-panel-hidden');

            if (this.model) {
                this.$el.addClass('col-md-2');

                let detailContainer = this.$el.parents('#main').find('.overview');
                detailContainer.removeClass('col-md-9 hidden-catalog-tree-panel');
                detailContainer.addClass('col-md-7');
            } else {
                this.$el.addClass('col-xs-12 col-lg-3');

                let listContainer = this.$el.parents('#main').find('.list-container');
                listContainer.removeClass('hidden-catalog-tree-panel');
                listContainer.addClass('col-xs-12 col-lg-9');
            }
        },

        hideUtilityElements() {
            let button = this.$el.find('button[data-action="collapsePanel"]');
            button.addClass('collapsed');
            button.find('span.toggle-icon-left').addClass('hidden');
            button.find('span.toggle-icon-right').removeClass('hidden');

            this.$el.addClass('catalog-tree-panel-hidden');

            if (this.model) {
                this.$el.removeClass('col-md-2');

                let detailContainer = this.$el.parents('#main').find('.overview');
                detailContainer.removeClass('col-md-7');
                detailContainer.addClass('col-md-9 hidden-catalog-tree-panel');
            } else {
                this.$el.removeClass('col-xs-12 col-lg-3');

                let listContainer = this.$el.parents('#main').find('.list-container');
                listContainer.removeClass('col-xs-12 col-lg-9');
                listContainer.addClass('hidden-catalog-tree-panel');
            }
        },

        getCatalogDataList: function () {
            let arr = [];
            this.catalogs.forEach(catalog => {
                if (catalog.categoriesIds && catalog.categoriesIds.length) {
                    arr.push({
                        key: `category-tree-${catalog.id}`,
                        name: catalog.id
                    });
                }
            });
            return arr;
        },
    })
);