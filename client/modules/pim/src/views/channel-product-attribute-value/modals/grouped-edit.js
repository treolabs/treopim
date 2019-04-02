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

Espo.define('pim:views/channel-product-attribute-value/modals/grouped-edit', 'views/modals/edit',
    Dep => Dep.extend({

        template: 'pim:channel-product-attribute-value/modals/grouped-edit',

        fullFormDisabled: true,

        sideFieldList: ['ownerUser', 'assignedUser', 'teams'],

        initialModel: null,

        setup() {
            this.buttonList = [];

            if ('saveDisabled' in this.options) {
                this.saveDisabled = this.options.saveDisabled;
            }

            if (!this.saveDisabled) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                });
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form'
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope);
            } else {
                this.header = this.getLanguage().translate('Edit');
                this.header += ': ' + this.getLanguage().translate(this.scope, 'scopeNames');
            }

            if (!this.fullFormDisabled) {
                if (!this.id) {
                    this.header = '<a href="#' + this.scope + '/create" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                } else {
                    this.header = '<a href="#' + this.scope + '/edit/' + this.id+'" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                }
            }

            var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
            this.header = iconHtml + this.header;

            this.initialModel = this.options.model;
            this.model = this.initialModel.clone();
            this.model.setDefs(Espo.Utils.cloneDeep(this.initialModel.defs));
            this.model.id = this.initialModel.id;

            this.sideFieldList = this.sideFieldList.filter(field => this.model.hasField(field));
        },

        data() {
            return {
                sideFieldList: this.sideFieldList.length ? this.sideFieldList : null,
                attributeName: this.model.get('attributeName')
            };
        },

        afterRender() {
            this.createRecordView();
        },

        createRecordView() {
            [...this.sideFieldList, 'attributeValue'].forEach(field => {
                let view = this.getFieldManager().getViewName(this.model.getFieldType(field));
                this.createView(field, view, {
                    mode: 'edit',
                    inlineEditDisabled: true,
                    model: this.model,
                    el: `${this.options.el} .field[data-name="${field}"]`,
                    name: field,
                    customLabel: field === 'attributeValue' ? this.model.get('attributeName') : null,
                }, view => {
                    view.render();
                });
            });
        },

        actionSave() {
            let $buttons = this.dialog.$el.find('.modal-footer button');
            $buttons.addClass('disabled').attr('disabled', 'disabled');

            if (this.validate()) {
                $buttons.removeClass('disabled').removeAttr('disabled');
                return;
            }

            let data = this.fetch();
            let newData = {};
            for (let key in data) {
                newData[Espo.utils.lowerCaseFirst(key.replace('attribute', ''))] = data[key];
            }

            this.ajaxPatchRequest(`ChannelProductAttributeValue/${this.model.id}`, newData)
                .then(response => {
                    this.notify('Saved', 'success');
                    this.initialModel.trigger('after:save');
                    this.initialModel.set(data);
                    $buttons.removeClass('disabled').removeAttr('disabled');
                    this.dialog.close();
                }, reason => {
                    $buttons.removeClass('disabled').removeAttr('disabled');
                });
        },

        validate () {
            let notValid = false;
            [...this.sideFieldList, 'attributeValue'].forEach(field => {
                notValid = this.getView(field).validate() || notValid;
            });
            return notValid;
        },

        fetch() {
            return [...this.sideFieldList, 'attributeValue'].reduce((prev, field) => {
                return {
                    ...prev,
                    ...this.getView(field).fetch()
                };
            }, {})
        }

    })
);

