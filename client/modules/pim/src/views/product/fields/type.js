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

Espo.define('pim:views/product/fields/type', 'views/fields/enum',
    Dep => Dep.extend({

        data() {
            return _.extend({
                optionList: this.model.options || []
            }, Dep.prototype.data.call(this));
        },

        setupOptions() {
            var productType = Espo.Utils.clone(this.getMetadata().get('pim.productType'));
            var typeName = {};
            this.params.options = [];
            for (var type in productType) {
                this.params.options.push(type)
                typeName[type] = productType[type].name;
            }

            this.translatedOptions = Espo.Utils.clone(this.getLanguage().translate('type', 'options', 'Product') || {});
            // Add default name if not exist translate
            if(typeof this.translatedOptions !== 'object') {
                this.translatedOptions = typeName;
            }
        }

    })
);
