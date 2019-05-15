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

Espo.define('pim:views/product-attribute-value/fields/value-container', 'views/fields/base',
    (Dep) => Dep.extend({

        listTemplate: 'pim:product-attribute-value/fields/base',

        detailTemplate: 'pim:product-attribute-value/fields/base',

        editTemplate: 'pim:product-attribute-value/fields/base',

        setup() {
            this.defs = this.options.defs || {};
            this.name = this.options.name || this.defs.name;
            this.params = this.options.params || this.defs.params || {};

            let type = this.model.get('attributeType') || 'base';
            this.createView('valueField', this.getFieldManager().getViewName(type), {
                el: `${this.options.el} > .field[data-name="valueField"]`,
                model: this.model,
                name: this.name,
                mode: 'list',
                defs: this.defs,
                params: this.params,
                inlineEditDisabled: true
            }, view => {
                view.render();
            });
        }

    })
);

