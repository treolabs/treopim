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

Espo.define('pim:views/product-image/fields/channels', 'treo-core:views/fields/filtered-link-multiple',
    Dep => Dep.extend({

        createDisabled: true,

        productId: null,

        foreignScope: 'Channel',

        selectBoolFilterList: ['notEntity', 'linkedWithProduct'],

        validations: ['required'],

        boolFilterData: {
            notEntity() {
                return Espo.Utils.clone(this.model.get(this.idsName));
            },
            linkedWithProduct() {
                return this.productId;
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.params.required = true;

            this.productId = this.model.productId;

            if (!this.model.isNew() && this.model.get('scope') === 'Channel') {
                this.wait(true);
                this.getData().then(response => {
                    let data = response.list.reduce((accumulator, current) => {
                        accumulator[this.idsName].push(current.id);
                        accumulator[this.nameHashName][current.id] = current.name;
                        return accumulator;
                    }, {[this.idsName]: [], [this.nameHashName]: {}});

                    this.model.set(data);
                    this.wait(false);
                });
            }
        },

        getData() {
            return this.ajaxGetRequest(`ProductImage/${this.model.id}/channels/${this.productId}`);
        }
    })
);