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

Espo.define('pim:views/product-attribute-value/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.handleValueModelDefsUpdating();
        },

        handleValueModelDefsUpdating() {
            if (this.model.get('attributeId')) {
                this.updateValueDefsInModel();
            }
            this.listenTo(this.model, 'change:attributeId', () => {
                if (this.model.get('attributeId')) {
                    this.updateValueDefsInModel();
                    this.clearView('middle');
                    this.gridLayout = null;
                    this.createMiddleView(() => this.reRender());
                }
            });
        },

        updateValueDefsInModel() {
            let type = this.model.get('attributeType');
            let typeValue = this.model.get('typeValue');
            if (type) {
                let fieldDefs = {
                    type: type,
                    options: typeValue,
                    view: type !== 'bool' ? this.getFieldManager().getViewName(type) : 'pim:views/fields/bool-required',
                    required: !!this.model.get('isRequired')
                };
                if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang', 'wysiwygMultiLang'].includes(type)) {
                    fieldDefs.isMultilang = true;
                    this.getFieldManager().getActualAttributeList(type, 'typeValue').splice(1).forEach(item => {
                        fieldDefs[`options${item.replace('typeValue', '')}`] = this.model.get(item);
                    });
                }
                if (type === 'unit') {
                    fieldDefs.measure = (typeValue || ['Length'])[0];
                }
                this.model.defs.fields.value = fieldDefs;
            }
        }

    })
);

