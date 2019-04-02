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

Espo.define('pim:views/attribute/modals/attribute-value', 'views/modal',
    Dep => Dep.extend({

        template: 'pim:attribute/modals/attribute-value',

        mode: null,

        scope: null,

        sideModel: null,

        sideFieldList: ['ownerUser', 'assignedUser', 'teams'],

        setup() {
            Dep.prototype.setup.call(this);

            this.name = this.options.name;
            this.scope = 'ProductAttributeValue';
            this.initialAttributes = this.model.getClonedAttributes();
            this.mode = this.model.getFieldParam(this.name, 'readOnly') ? 'detail' : 'edit';
            this.getModelFactory().create(this.scope, model => {
                this.sideModel = model;
                this.sideModel.set(this.options.attributeValueData);
                this.sideFieldList = this.sideFieldList.filter(field => this.sideModel.hasField(field));
            });
            this.setupHeader();
            this.setupButtonList();

            this.listenToOnce(this, 'after:render', () => {
                this.renderValueField();
                this.renderSideFields();
            })
        },

        renderValueField() {
            let type = this.model.getFieldParam(this.name, 'type');
            let viewName = type !== 'bool' ? this.getFieldManager().getViewName(type) : 'pim:views/fields/bool-required';
            this.createView(this.name, viewName, {
                el: `${this.options.el} .field[data-name="${this.name}"]`,
                model: this.model,
                mode: this.mode,
                inlineEditDisabled: true,
                defs: {
                    name: this.name,
                },
                params: {
                    required: this.model.getFieldParam(this.name, 'required')
                }
            }, view => {
                view.render();
            });
        },

        renderSideFields() {
            this.sideFieldList.forEach(field => {
                let type = this.sideModel.getFieldParam(field, 'type');
                let viewName = this.getFieldManager().getViewName(type);
                this.createView(field, viewName, {
                    el: `${this.options.el} .field[data-name="${field}"]`,
                    model: this.sideModel,
                    mode: this.mode,
                    inlineEditDisabled: true,
                    defs: {
                        name: field,
                    },
                    params: {
                        required: this.sideModel.getFieldParam(field, 'required')
                    }
                }, view => {
                    view.render();
                });
            });
        },

        data() {
            return {
                name: this.name,
                scope: this.scope,
                sideFieldList: this.sideFieldList
            };
        },

        setupHeader() {
            this.header = `${this.translate(this.mode === 'detail' ? 'View' : 'Edit')}: ${this.translate('ProductAttributeValue', 'scopeNames')}`;
        },

        setupButtonList() {
            this.buttonList = [];
            if (this.mode === 'edit') {
                this.buttonList.push({
                    name: 'save',
                    label: this.translate('Save'),
                    style: 'primary',
                });
            }
            this.buttonList.push({
                name: 'cancel',
                label: this.translate('Cancel')
            });
        },

        actionSave() {
            if (this.validate()) {
                return;
            }
            let inputLanguageList = (this.getConfig().get('inputLanguageList') || [])
            .map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));

            let view = this.getView(this.name);
            let data = view.fetch();
            if (data[this.name] === '') {
                data[this.name] = null;
            }
            let item = {
                attributeId: this.name,
                value: data[this.name],
                ...this.fetchSideFields()
            };

            let additionalData = this.getAdditionalFieldData(view, data);
            if (additionalData) {
                item.data = additionalData;
            }

            inputLanguageList.forEach(lang => item[`value${lang}`] = data[`${this.name}${lang}`] || null);
            this.ajaxPutRequest(`Markets/Product/${this.options.parentModel.id}/attributes`, [item])
            .then(response => {
                this.model.trigger('updateAttributes');
                this.options.parentModel.trigger('after:attributesSave');
                this.notify('Saved', 'success');
            });
        },

        getAdditionalFieldData(view, data) {
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
            return additionalData;
        },

        fetchSideFields() {
            return this.sideFieldList.reduce((prev, current) => {
                return {...prev, ...this.getView(current).fetch()};
            }, {});
        },

        actionCancel: function () {
            this.model.set(this.initialAttributes);
            this.dialog.close();
        },

        validate() {

            let notValidate = false;

            let viewsValidate = [...this.sideFieldList, this.name];

            viewsValidate.forEach((view) => {
                if (this.getView(view).validate()) {
                    notValidate = true;
                }
            });

            return notValidate;
        }
    })
);

