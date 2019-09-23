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
            this.name = this.options.name || this.defs.name;

            this.getModelFactory().create(this.model.name, model => {
                model = this.getConfiguratedValueModel(model);
                this.model = model;
                this.createValueFieldView();
            });
        },

        getConfiguratedValueModel(model) {
            model = this.model.clone();
            model.id = this.model.id;
            model.defs = Espo.Utils.cloneDeep(this.model.defs);
            return model;
        },

        createValueFieldView() {
            this.updateDataForValueField();
            this.updateModelDefs();

            this.clearView('valueField');

            let type = this.model.get('attributeType') || 'base';
            this.createView('valueField', this.getValueFieldView(type), {
                el: `${this.options.el} > .field[data-name="valueField"]`,
                model: this.model,
                name: this.name,
                mode: this.mode,
                inlineEditDisabled: true
            }, view => {
                view.render();
            });
        },

        updateModelDefs() {
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
        },

        updateDataForValueField() {
            let data = this.model.get('data') || {};
            Object.keys(data).forEach(param => this.model.set({[`value${Espo.Utils.upperCaseFirst(param)}`]: data[param]}));
        },

        getValueFieldView(type) {
            return this.getFieldManager().getViewName(type);
        },

        fetch() {
            let data = {};
            let view = this.getView('valueField');
            if (view) {
                _.extend(data, view.fetch());
            }
            return data;
        },

        validate() {
            let validate = false;
            let view = this.getView('valueField');
            if (view) {
                validate = view.validate();
            }
            if (!validate) {
                validate = this.validateColumn();
            }
            return validate;
        },

        setMode(mode) {
            Dep.prototype.setMode.call(this, mode);

            let valueField = this.getView('valueField');
            if (valueField) {
                valueField.setMode(mode);
            }
        }

    })
);

