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

Espo.define('pim:views/product-attribute-value/fields/enum-with-adding', 'views/fields/enum',
    Dep => Dep.extend({

        editTemplate: 'pim:product-attribute-value/fields/enum-with-adding/edit',

        events: {
            'click [data-action="addAttributeValueOption"]': function (e) {
                this.actionAddAttributeValueOption();
            }
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                this.$el.css({ position: 'relative' });
            }
        },

        actionAddAttributeValueOption() {
            if (this.getView('newAttributeValue')) {
                this.clearView('newAttributeValue');
            }

            this.$el.append('<div class="field" data-name="newAttributeValue" style="position: relative;"></div>');
            this.createView('newAttributeValue', 'pim:views/product-attribute-value/fields/varchar-with-remove', {
                el: this.options.el + ' .field[data-name="newAttributeValue"]',
                model: this.model,
                mode: 'edit',
                defs: {
                    name: 'newAttributeValue'
                }
            }, view => {
                view.events['keypress .main-element'] = (e) => {
                    if (e.keyCode === 13) {
                        const value = view.$element.val().trim();

                        if (value) {
                            const typeValueParams = { typeValue: this.model.get('typeValue') };

                            if (this.getConfig().get('isMultilangActive') && this.model.get('attributeIsMultilang')) {
                                (this.getConfig().get('inputLanguageList') || []).forEach(lang => {
                                    const field = lang.split('_').reduce((prev, curr) =>
                                        prev + Espo.Utils.upperCaseFirst(curr.toLocaleLowerCase()), 'typeValue');

                                    typeValueParams[field] = [...(this.model.get(field) || []), value];
                                });
                            }

                            this.defs.params.options = this.defs.params.options || [];

                            if (!this.defs.params.options.includes(value)) {
                                this.defs.params.options.push(value);
                            }

                            if (!(typeValueParams.typeValue || []).includes(value)) {
                                typeValueParams.typeValue.push(value);
                            }

                            this.notify('Saving...');
                            this.ajaxPutRequest(`Attribute/${this.model.get('attributeId')}`, typeValueParams)
                                .then(response => {
                                    this.notify('Saved', 'success');
                                    this.reRender();
                                    this.$element.val(value);
                                    this.trigger('change');
                                });
                        }
                    }
                };

                view.render();
            });
        },
    }));