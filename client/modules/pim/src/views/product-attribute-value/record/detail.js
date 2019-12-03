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
            this.updateModelDefs();
            this.listenTo(this.model, 'change:attributeId', () => {
                this.updateModelDefs();
                if (this.model.get('attributeId')) {
                    this.clearView('middle');
                    this.gridLayout = null;
                    this.createMiddleView(() => this.reRender());
                }
            });
        },

        updateModelDefs() {
            // prepare self
            let self = this;

            // readOnly
            self.changeFieldsReadOnlyStatus(['attribute', 'channels', 'product', 'scope'], !self.model.get('isCustom'));

            if (self.model.get('attributeId')) {
                // prepare data
                let type = self.model.get('attributeType');
                let typeValue = self.model.get('typeValue');

                if (type) {
                    // prepare field defs
                    let fieldDefs = {
                        type: type,
                        options: typeValue,
                        view: type !== 'bool' ? self.getFieldManager().getViewName(type) : 'pim:views/fields/bool-required',
                        required: !!self.model.get('isRequired')
                    };

                    // for unit
                    if (type === 'unit') {
                        fieldDefs.measure = (typeValue || ['Length'])[0];
                    }

                    // set field defs
                    self.model.defs.fields.value = fieldDefs;

                    // for multi-language
                    $.each(self.model.defs.fields, function (field, row) {
                        if (row.multilangField === 'value') {
                            self.model.defs.fields[field] = fieldDefs;
                        }
                    });
                }
            }
        },

        changeFieldsReadOnlyStatus(fields, condition) {
            fields.forEach(field => this.model.defs.fields[field].readOnly = condition);
        },

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            let view = this.getFieldView('value');
            if (view) {
                this.extendFieldData(view, data);
            }
            return data;
        },

        extendFieldData(view, data) {
            let additionalData = false;

            if (view.type === 'unit') {
                let actualFieldDefs = this.getMetadata().get(['fields', view.type, 'actualFields']) || [];
                let actualFieldValues = this.getFieldManager().getActualAttributes(view.type, view.name) || [];
                actualFieldDefs.forEach((field, i) => {
                    if (field) {
                        additionalData = additionalData || {};
                        additionalData[field] = data[actualFieldValues[i]];
                    }
                });
            }

            if (view.type === 'image') {
                _.extend((data || {}), {value: (data || {}).valueId});
            }

            if (additionalData) {
                _.extend((data || {}), {data: additionalData});
            }
        }

    })
);

