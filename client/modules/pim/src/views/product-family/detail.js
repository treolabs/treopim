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

Espo.define('pim:views/product-family/detail', 'pim:views/detail',
    Dep => Dep.extend({

        selectBoolFilterLists: {
            productFamilyAttributes: ['notLinkedWithProductFamily']
        },

        boolFilterData: {
            productFamilyAttributes: {
                notLinkedWithProductFamily() {
                    return this.model.id;
                }
            }
        },

        actionSelectRelatedAttribute(data) {
            var link = data.link;
            var scope = 'Attribute';
            var self = this;

            var filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};
            for (var filterName in filters) {
                if (typeof filters[filterName] == 'function') {
                    var filtersData = filters[filterName].call(this);
                    if (filtersData) {
                        filters[filterName] = filtersData;
                    } else {
                        delete filters[filterName];
                    }
                }
            }

            var primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;
            if (typeof primaryFilterName == 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }

            var boolFilterList = data.boolFilterList || Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);
            if (typeof boolFilterList == 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                filters: filters,
                massRelateEnabled: false,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
                boolFilterData: this.getBoolFilterData(link)
            }, function (dialog) {
                dialog.render();
                this.notify(false);
                dialog.once('select', selectObj => {
                    var ids = [];
                    selectObj.forEach(model => ids.push(model.id));

                    if (ids.length) {
                        Promise.all(ids.map(id => this.ajaxPostRequest('ProductFamilyAttribute', {
                            productFamilyId: this.model.id,
                            attributeId: id,
                            isRequired: false
                        }), this)).then(response => {
                            this.notify('Linked', 'success');
                            this.updateRelationshipPanel('productFamilyAttributes');
                        });
                    }
                }, this);
            }.bind(this));
        },

    })
);

