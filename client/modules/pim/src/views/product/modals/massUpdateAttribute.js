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

Espo.define('pim:views/product/modals/massUpdateAttribute', 'views/modals/mass-update',
    Dep => Dep.extend({
        template: 'pim:modals/massUpdateAttribute',

        data: function () {
            return {
                scope: this.scope,
                attributes: this.attributes
            };
        },

        events: {
            'click button[data-action="update"]': function () {
                this.actionUpdate();
            },
            'click a[data-action="add-attribute"]': function (e) {
                let attributeId = $(e.currentTarget).data('attribute-id');
                this.actionRenderAttribute(attributeId);
            },
            'click button[data-action="reset"]': function (e) {
                this.actionReset();
            }
        },

        setup: function () {
            this.createButtonList();
            this.copyOptionsToThis();
            this.initAttributes();

            this.renderedAtrributes = [];
        },

        /**
         * Add attribute to fields container
         * @param attributeId
         */
        actionRenderAttribute: function (attributeId) {
            this.enableButton('update');

            this.$el.find('[data-action="reset"]').removeClass('hidden');
            this.$el.find('ul.filter-list li[data-attribute-id="' + attributeId + '"]').addClass('hidden');

            if (this.$el.find('ul.filter-list li:not(.hidden)').size() == 0) {
                this.$el.find('button.select-field').addClass('disabled').attr('disabled', 'disabled');
            }

            this.notify('Loading...');
            let attribute = this.attributes[attributeId];

            let label = attribute.name;
            let html =
                '<div class="cell form-group col-sm-6" data-attribute-id="' + attributeId + '">'
                    + '<label class="control-label">' + label + '</label>'
                    + '<div class="field" data-attribute-id="' + attributeId + '" />'
                + '</div>';

            this.$el.find('.fields-container').append(html);
            this.updateModelDefs(attribute);

            this.createValueFieldView(attribute);
        },

        /**
         * @param attribute
         */
        createValueFieldView(attribute) {
            this.model.set({[`value${attribute.attributeId}`]: null});
            this.model.set({value: null});

            this.createView(attribute.attributeId, this.getFieldManager().getViewName(attribute.attributeType), {
                el: this.getSelector() + ' .field[data-attribute-id="' + attribute.attributeId + '"]',
                model: this.model,
                name: `value${attribute.attributeId}`,
                mode: 'edit'
            }, view => {
                this.renderedAtrributes.push(attribute.attributeId);
                view.render();
                view.notify(false);
            });
        },

        /**
         * @param attribute
         */
        updateModelDefs(attribute) {
            if (attribute.attributeType) {
                let fieldDefs = {
                    type: attribute.type,
                    options: attribute.typeValue,
                    measure: (attribute.typeValue || ['Length'])[0],
                    view: attribute.attributeType !== 'bool' ? this.getFieldManager().getViewName(attribute.attributeType) : 'pim:views/fields/bool-required',
                    required: false
                };
                if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang', 'wysiwygMultiLang'].includes(attribute.attributeType)) {
                    fieldDefs.isMultilang = true;
                }

                this.model.defs.fields['value' + attribute.attributeId] = fieldDefs;
            }
        },
        /**
         * Action Reset
         */
        actionReset: function () {
            this.renderedAtrributes.forEach(function (attributeId) {
                this.clearView(attributeId);
                this.$el.find('.cell[attribute-id="' + attributeId + '"]').remove();
            }, this);

            this.renderedAtrributes = [];

            this.model.clear();

            this.$el.find('[data-action="reset"]').addClass('hidden');

            this.$el.find('button.select-field').removeClass('disabled').removeAttr('disabled');
            this.$el.find('ul.filter-list').find('li').removeClass('hidden');

            this.disableButton('update');
        },

        /**
         * Action Update
         */
        actionUpdate: function () {
            this.disableButton('update');
            if (this.isValidAttributes()){
                let count = 0;

                this.renderedAtrributes.forEach(attributeId => {
                    this.notify('Saving...');

                    let whereUpdate = this.getWhereUpdate(attributeId);
                    let value = this.getValue(attributeId);
                    debugger
                    $.ajax({
                        url: 'ProductAttributeValue' + '/action/massUpdate',
                        type: 'PUT',
                        data: JSON.stringify({
                            attributes: {value: value},
                            ids: null,
                            where: whereUpdate,
                            selectData: ['attributeId', 'attributeName', 'productId', 'productName'],
                            byWhere: true
                        }),
                        success: function (result) {
                            count += (result || {}).count;
                            this.trigger('after:update', count);
                            this.notify(false);
                        }.bind(this),
                        error: function () {
                            this.notify('Error occurred', 'error');
                            this.enableButton('update');
                        }.bind(this)
                    });
                });
            }
        },
        /**
         * @returns {array}
         */
        getWhereUpdate(attributeId) {
            let whereUpdate = this.where ? this.where : [];
            whereUpdate.push({attribute: 'attributeId', type: "equals", value: attributeId});
            whereUpdate.push({
                type: "or",
                value: [
                    {attribute: 'product.type', type: "notEquals", value: 'productVariant'},
                    {attribute: 'product.data', type: "contains", value: attributeId}
                ]
            });
            if (this.ids) {
                whereUpdate.push({attribute: 'productId', type: "in", value: this.ids});
            }

            return whereUpdate;
        },

        /**
         * @param attributeId
         * @returns {string}
         */
        getValue(attributeId) {
            let view = this.getView(attributeId);
            let value = view.model.get('value' + attributeId);

             if((this.attributes[attributeId].attributeType || '') === 'image') {
                value = view.model.get('value' + attributeId + 'Id');
            } else if (Array.isArray(value)) {
                value = JSON.stringify(value);
            }

            return value
        },

        /**
         * Check isValidAttributes
         * @returns {boolean}
         */
        isValidAttributes() {
            this.renderedAtrributes.forEach(field => {
                let view = this.getView(field);
                let notValid = false;
                notValid = view.validate() || notValid;
                if (notValid && !view.model.get('value' + field)) {
                    this.notify('Not valid', 'error');
                    this.enableButton('update');
                    return false;
                }
            });
            return true;
        },

        /**
         * Init attribute
         */
        initAttributes() {
            this.wait(true);

            this.getModelFactory().create('ProductAttributeValue', function (model) {
               this.ajaxPostRequest('Product/massUpdateAttribute/getAttributes', {ids: this.ids}).then(response => {
                   this.attributes = response;
                   this.wait(false);
               });



                this.model = model;
            }.bind(this));
        },

        /**
         * Create buttons List
         */
        createButtonList()
        {
            this.buttonList = [
                {
                    name: 'update',
                    label: 'Update',
                    style: 'danger',
                    disabled: true
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];
        },

        /**
         * Add options to this
         */
        copyOptionsToThis() {
            this.scope = this.options.scope;
            this.ids = this.options.ids;
            this.where = this.options.where;
            this.selectData = this.options.selectData;
            this.byWhere = this.options.byWhere;

            this.header = this.translate(this.scope, 'scopeNamesPlural') + ' &raquo ' + this.translate('Mass Update');
        }
    })
);

