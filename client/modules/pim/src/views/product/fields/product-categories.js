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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

Espo.define('pim:views/product/fields/product-categories', 'views/fields/link-multiple',
    Dep => Dep.extend({

        autocompleteDisabled: true,

        selectBoolFilterList: ['notEntity'],

        boolFilterData: {
            notEntity() {
                return this.ids;
            }
        },

        getBoolFilterData() {
            let data = {};
            this.selectBoolFilterList.forEach(item => {
                if (typeof this.boolFilterData[item] === 'function') {
                    data[item] = this.boolFilterData[item].call(this);
                }
            });
            return data;
        },

        setup() {
            Dep.prototype.setup.call(this);

            if (this.mode !== 'list') {
                delete this.events[`click button[data-action="selectLink"]`];
                this.addActionHandler('selectLink', () => {
                    this.notify('Loading...');

                    let viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select')  || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode !== 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        boolFilterData: this.getBoolFilterData(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true,
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null,
                        mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                        forceSelectAllAttributes: this.forceSelectAllAttributes
                    }, dialog => {
                        dialog.render();
                        this.notify(false);
                        this.listenToOnce(dialog, 'select', models => {
                            this.clearView('dialog');
                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }
                            models.forEach(model => this.addLink(model.id, `${model.get('categoryName')} (${model.get('scope')})`));
                        });
                    });
                });
            }
        }

    })
);
