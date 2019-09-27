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

Espo.define('pim:views/product/list', ['pim:views/list', 'search-manager'],
    (Dep, SearchManager) => Dep.extend({

        template: 'pim:product/list',

        catalogTreeData: null,

        setup() {
            Dep.prototype.setup.call(this);

            if (this.getAcl().check('Catalog', 'read') && this.getAcl().check('Category', 'read')) {
                this.setupCatalogTreePanel();
            }
        },

        setupCatalogTreePanel() {
            this.createView('catalogTreePanel', 'pim:views/product/record/catalog-tree-panel', {
                el: '#main > .catalog-tree-panel',
                scope: this.scope
            }, view => {
                view.render();
                view.listenTo(view, 'select-category', data => {
                    this.catalogTreeData = data || {};
                    this.updateCollectionWithCatalogTree();
                    this.collection.fetch();
                });
            });
        },

        updateCollectionWithCatalogTree() {
            const defaultFilters = this.searchManager.get();
            let extendedFilters = _.extend(Espo.Utils.cloneDeep(defaultFilters), this.catalogTreeData);
            this.searchManager.set(extendedFilters);
            this.collection.where = this.searchManager.getWhere();
            this.searchManager.set(defaultFilters);
        },

        data() {
            return {
                isCatalogTreePanel: this.getAcl().check('Catalog', 'read') && this.getAcl().check('Category', 'read')
            }
        },

        setupSearchManager() {
            let collection = this.collection;

            var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime(), this.getSearchDefaultData());
            searchManager.scope = this.scope;

            if (this.options.params.showFullListFilter) {
                searchManager.set(_.extend(searchManager.get(), {advanced: Espo.Utils.cloneDeep(this.options.params.advanced)}));
            }

            if ((this.options.params || {}).boolFilterList) {
                searchManager.set({
                    textFilter: '',
                    advanced: {},
                    primary: null,
                    bool: (this.options.params || {}).boolFilterList.reduce((acc, curr) => {
                        acc[curr] = true;
                        return acc;
                    }, {})
                });
            } else {
                searchManager.loadStored();
            }

            collection.where = searchManager.getWhere();
            this.searchManager = searchManager;
        },

        resetSorting() {
            Dep.prototype.resetSorting.call(this);

            let catalogTreePanel = this.getView('catalogTreePanel');
            if (catalogTreePanel) {
                catalogTreePanel.trigger('resetFilters');
            }
        }

    })
);

