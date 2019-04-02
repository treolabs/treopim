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

Espo.define('pim:views/product/record/catalog-tree-panel/category-search', 'view',
    Dep => Dep.extend({

        template: 'pim:product/record/catalog-tree-panel/category-search',

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        data() {
            return {
                scope: this.scope
            }
        },

        setup() {
            this.scope = this.options.scope || this.scope;
            this.lookupData = [];
            this.options.categories.forEach(category => {
                let firstParentId;
                if (category.categoryRoute) {
                    firstParentId = category.categoryRoute.split('|').find(element => element);
                }
                this.options.catalogs.forEach(catalog => {
                    if (catalog.categoryId === category.id || (firstParentId && catalog.categoryId === firstParentId)) {
                        category.catalogId = catalog.id;
                        this.lookupData.push({
                            value: category.name,
                            data: Espo.Utils.cloneDeep(category)
                        });
                    }
                });
            });
        },

        afterRender() {
            if (this.el) {
                this.$el.find('input').autocomplete({
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    lookup: this.lookupData,
                    formatResult: function (suggestion, value) {
                        let category = suggestion.data;
                        let catalog = this.options.catalogs.find(catalog => catalog.id === category.catalogId);
                        return catalog.name + ' > ' + category.name;
                    }.bind(this),
                    onSelect: function (suggestion) {
                        this.$el.find('input').val('');
                        this.trigger('category-search-select', suggestion.data);
                    }.bind(this)
                });
            }
        },
    })
);