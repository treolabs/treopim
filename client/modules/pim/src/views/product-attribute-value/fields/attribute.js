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

Espo.define('pim:views/product-attribute-value/fields/attribute', 'views/fields/link',
    Dep => Dep.extend({

        setup() {
            this.mandatorySelectAttributeList = ['type', 'typeValue'];
            let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
            if (this.getConfig().get('isMultilangActive') && inputLanguageList.length) {
                this.typeValueFields = inputLanguageList.map(lang => {
                    return lang.split('_').reduce((prev, curr) => prev + Espo.Utils.upperCaseFirst(curr.toLocaleLowerCase()), 'typeValue');
                });
                this.mandatorySelectAttributeList = this.mandatorySelectAttributeList.concat(this.typeValueFields);
            }
            this.createDisabled = !this.getAcl().check('Attribute', 'create');

            Dep.prototype.setup.call(this);
        },

        select(model) {
            this.setAttributeFieldsToModel(model);

            Dep.prototype.select.call(this, model);
        },

        setAttributeFieldsToModel(model) {
            let attributes = {
                attributeType: model.get('type'),
                typeValue: model.get('typeValue'),
            };
            (this.typeValueFields || []).forEach(item => attributes[item] = model.get(item));
            this.model.set(attributes);
        }

    })
);

