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
                this.updateDataForValueField();
                this.updateModelDefs();

                model = this.getConfiguratedValueModel(model);
                this.model = model;
                this.createValueFieldView();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit' && ['multiEnum'].includes(this.model.get('attributeType'))) {
                this.$el.addClass('over-visible');
            }
        },

        getConfiguratedValueModel(model) {
            model = this.model.clone();
            model.id = this.model.id;
            model.defs = Espo.Utils.cloneDeep(this.model.defs);
            return model;
        },

        createValueFieldView() {
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
            // prepare data
            let type = this.model.get('attributeType');
            let typeValue = this.model.get('typeValue');

            if (type) {
                // prepare field defs
                let fieldDefs = {
                    type: type,
                    options: typeValue,
                    view: type !== 'bool' ? this.getFieldManager().getViewName(type) : 'pim:views/fields/bool-required',
                    required: !!this.model.get('isRequired'),
                    readOnly: (type === 'enum' || type === 'multiEnum') && !!this.model.get('locale')
                };

                if (type === 'unit') {
                    fieldDefs.measure = (typeValue || ['Length'])[0];
                }

                // set field defs
                this.model.defs.fields.value = fieldDefs;
            }
        },

        updateDataForValueField() {
            let data = this.model.get('data') || {};
            Object.keys(data).forEach(param => this.model.set({[`${this.name}${Espo.Utils.upperCaseFirst(param)}`]: data[param]}));

            if (this.model.get('attributeType') === 'image') {
                this.model.set({[`${this.name}Id`]: this.model.get(this.name)});
            }
        },

        getValueFieldView(type) {
            return this.getMetadata().get(['clientDefs', this.scope, 'additionalFieldsParams', type, 'view']) || this.getFieldManager().getViewName(type);
        },

        fetch() {
            let data = {};
            let view = this.getView('valueField');
            if (view) {
                _.extend(data, view.fetch());
                this.extendValueData(view, data);
            }
            return data;
        },

        extendValueData(view, data) {
            data = data || {};
            const additionalData = {};
            if (view.type === 'unit') {
                let actualFieldDefs = this.getMetadata().get(['fields', view.type, 'actualFields']) || [];
                let actualFieldValues = this.getFieldManager().getActualAttributes(view.type, view.name) || [];
                actualFieldDefs.forEach((field, i) => {
                    if (field) {
                        additionalData[field] = data[actualFieldValues[i]];
                    }
                });
                if (additionalData) {
                    _.extend(data, {data: additionalData});
                }
            }
            if (view.type === 'image') {
                _.extend(data, {[this.name]: data[`${this.name}Id`]});
            }
        },

        validate() {
            let validate = false;
            let view = this.getView('valueField');
            if (view) {
                validate = view.validate();
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

