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

Espo.define('pim:views/product/search/filter', 'views/search/filter', function (Dep) {

    return Dep.extend({

        template: 'pim:product/search/filter',

        setup: function () {
            let name = this.name = this.options.name;
            name = name.split('-')[0];
            this.clearedName = name;
            let type = this.model.getFieldType(name) || this.options.params.type;

            if (type) {
                let viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

                let params = {};
                if (this.options.params.isTypeValue) {
                    params = {
                        options: this.options.params.options,
                        translatedOptions: this.options.params.translatedOptions
                    }
                }
                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    name: name,
                    params: params,
                    searchParams: this.options.searchParams,
                });
            }
        },

        data: function () {
            return _.extend({
                label: this.options.params.isAttribute ? this.options.params.label : this.getLanguage().translate(this.name, 'fields', this.scope),
                clearedName: this.clearedName
            }, Dep.prototype.data.call(this));
        }
    });
});

