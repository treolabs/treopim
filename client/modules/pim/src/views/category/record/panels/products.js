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

Espo.define('pim:views/category/record/panels/products', 'views/record/panels/relationship',
    Dep => Dep.extend({

        boolFilterData: {
            notLinkedWithCategory() {
                return this.model.id;
            },
            onlyCatalogProducts() {
                return true;
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            let create = this.buttonList.find(item => item.action === (this.defs.createAction || 'createRelated'));
            if (create) {
                create.data.fullFormDisabled = true;
            }

            let select = this.actionList.find(item => item.action === (this.defs.selectAction || 'selectRelated'));

            if (select) {
                select.data = {
                    link: this.link,
                    scope: 'Product',
                    boolFilterListCallback: 'getSelectBoolFilterList',
                    boolFilterDataCallback: 'getSelectBoolFilterData',
                    primaryFilterName: this.defs.selectPrimaryFilterName || null
                };
            }
            if (typeof this.model.get('hasChildren') === 'undefined') {
                this.listenToOnce(this.model, 'sync', () => {
                    this.listenToOnce(this, 'after:render', () => {
                        this.setupButtonAndActionLists();
                    });
                    this.reRender();
                });
            } else {
                this.listenToOnce(this, 'after:render', () => {
                    this.setupButtonAndActionLists();
                });
            }
        },

        setupButtonAndActionLists() {
            let btnGroup = this.$el.parent().find('.btn-group');
            if (this.model.get('hasChildren')) {
                btnGroup.hide();
            } else {
                btnGroup.show();
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

    })
);
