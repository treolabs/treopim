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

Espo.define('pim:views/product-image/fields/scope', 'views/fields/enum',
    Dep => Dep.extend({

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            let productsIds = this.model.get('productsIds') || {};
            let productsColumns = this.model.get('productsColumns') || {};
            let productId = this.model.productId;

            if (!productsIds.includes(productId)) {
                productsIds.push(productId);
            }
            productsColumns[productId] = productsColumns[productId] || {};
            productsColumns[productId][this.name] = data[this.name];

            data.productsIds = productsIds;
            data.productsColumns = productsColumns;

            return data;
        },

    })
);