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

Espo.define('pim:views/product/detail', 'pim:views/detail',
    Dep => Dep.extend({

        selectRelatedFilters: {},

        selectBoolFilterLists: {
            attributes: ['notLinkedWithProduct'],
        },

        boolFilterData: {
            attributes: {
                notLinkedWithProduct() {
                    return this.model.id;
                },
            },
        },

        actionSelectAttribute(data) {
            let link = data.link;
            let scope = data.scope || this.model.defs['links'][link].entity;
            let afterSelectCallback = data.afterSelectCallback;
            let boolFilterListCallback = data.boolFilterListCallback;
            let boolFilterDataCallback = data.boolFilterDataCallback;
            let panelView = this.getPanelView(link);

            if (!this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }
            let foreign = this.model.defs['links'][link].foreign;

            let massRelateEnabled = false;
            if (foreign) {
                let foreignType = this.getMetadata().get('entityDefs.' + scope + '.links.' + foreign + '.type');
                if (foreignType == 'hasMany') {
                    massRelateEnabled = true;
                }
            }

            let filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};
            for (let filterName in filters) {
                if (typeof filters[filterName] == 'function') {
                    let filtersData = filters[filterName].call(this);
                    if (filtersData) {
                        filters[filterName] = filtersData;
                    } else {
                        delete filters[filterName];
                    }
                }
            }

            let primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;
            if (typeof primaryFilterName == 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }

            let boolFilterList = data.boolFilterList || Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);
            if (typeof boolFilterList == 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            if (boolFilterListCallback && panelView && typeof panelView[boolFilterListCallback] === 'function') {
                boolFilterList = panelView[boolFilterListCallback]();
            }

            let boolfilterData = [];
            if (boolFilterDataCallback && panelView && typeof panelView[boolFilterDataCallback] === 'function') {
                boolfilterData = panelView[boolFilterDataCallback](boolFilterList);
            }

            let viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                listLayout:  data.listLayout,
                filters: filters,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
                boolFilterData: boolfilterData
            }, dialog => {
                dialog.render();
                this.notify(false);
                dialog.once('select', selectObj => {
                    if (afterSelectCallback && panelView && typeof panelView[afterSelectCallback] === 'function') {
                        panelView[afterSelectCallback](selectObj);
                    } else {
                        let data = {};
                        if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                            let ids = [];
                            selectObj.forEach(function (model) {
                                ids.push(model.id);
                            });
                            data.ids = ids;
                        } else {
                            if (selectObj.massRelate) {
                                data.massRelate = true;
                                data.where = selectObj.where;
                            } else {
                                data.id = selectObj.id;
                            }
                        }
                        $.ajax({
                            url: this.scope + '/' + this.model.id + '/' + link,
                            type: 'POST',
                            data: JSON.stringify(data),
                            success: function () {
                                this.notify('Linked', 'success');
                                this.updateRelationshipPanel(link);
                                this.model.trigger('after:relate', link);
                                this.afterSelectAttributeCallback(data);
                            }.bind(this),
                            error: function () {
                                this.notify('Error occurred', 'error');
                            }.bind(this)
                        });
                    }
                });
            });
        },

        afterSelectAttributeCallback(data) {}

    })
);

