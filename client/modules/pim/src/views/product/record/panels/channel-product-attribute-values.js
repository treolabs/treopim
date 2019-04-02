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

Espo.define('pim:views/product/record/panels/channel-product-attribute-values', 'views/record/panels/bottom',
    Dep => Dep.extend({

        channels: [],

        template: "pim:product/record/panels/channel-product-attribute-values",

        events: {
            'click [data-action="selectChannelAttributes"]': function(e) {
                this.selectChannelAttributes($(e.currentTarget).data('channel'));
            },
            'click [data-action="addChannelAttribute"]': function(e) {
                this.addChannelAttribute($(e.currentTarget).data('channel'));
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.once('after:render', () => {
                this.actionRefresh();
            });

            this.listenTo(this.model, 'after:relate after:unrelate', data => {
                if (data === 'attributes' || data === 'categories') {
                    this.actionRefresh();
                }
            }, this);

            this.listenTo(this.model, 'after:save', () => {
                this.actionRefresh();
            });
        },

        buildChannels() {
            let listLayout = [
                {
                    name: 'attributeName',
                    notSortable: true
                },
                {
                    name: 'attributeValue',
                    notSortable: true
                }
            ];

            this.channels = [];

            this.ajaxGetRequest(`Markets/Product/${this.model.id}/channelAttributes`)
                .then(data => {
                    this.clearNestedViews();
                    this.$el.html('');

                    if (!data || !data.length) {
                        this.showEmptyData();
                    }

                    let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
                    let inputLanguageListKeys = false;
                    if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                        inputLanguageListKeys = inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
                    }

                    this.channels = data;

                    data.forEach(channel => {
                        this.getCollectionFactory().create('ChannelProductAttributeValue', collection => {
                            collection.total = channel.attributes.length;

                            let hiddenLocales = inputLanguageList.filter(item => !(channel.locales || []).includes(item));

                            channel.attributes.forEach(attribute => {
                                this.getModelFactory().create('ChannelProductAttributeValue', model => {
                                    let defs = Espo.Utils.cloneDeep(model.defs);
                                    defs.fields = {
                                        ...defs.fields,
                                        attributeName: {
                                            type: 'varchar',
                                            required: true,
                                            readonly: true
                                        },
                                        attributeValue: {
                                            type: attribute.attributeType,
                                            required: false,
                                            options: attribute.attributeTypeValue,
                                            hiddenLocales: hiddenLocales,
                                            measure: attribute.attributeType === 'unit' ? (attribute.attributeTypeValue || [])[0] : null
                                        },
                                        attributeIsMultiChannel: {
                                            type: 'bool',
                                            required: false,
                                            readonly: true
                                        },
                                        editable: {
                                            type: 'bool',
                                            required: false,
                                            readonly: true
                                        },
                                        deletable: {
                                            type: 'bool',
                                            required: false,
                                            readonly: true
                                        }
                                    };

                                    let data = Espo.Utils.cloneDeep(attribute);

                                    if (Espo.Utils.isObject(attribute.attributeData)) {
                                        Object.keys(attribute.attributeData).forEach(param => data[`attributeValue${Espo.Utils.upperCaseFirst(param)}`] = attribute.attributeData[param]);
                                    }

                                    if (inputLanguageListKeys) {
                                        if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang'].indexOf(attribute.attributeType) > -1) {
                                            inputLanguageListKeys.forEach(item => {
                                                data[`attributeValue${item}`] = attribute[`attributeValue${item}`];
                                                defs.fields.attributeValue[`options${item}`] = attribute[`attributeTypeValue${item}`];
                                            });
                                        }
                                    }

                                    model.setDefs(defs);
                                    model.set(data);
                                    model.id = attribute.channelProductAttributeValueId;
                                    collection.add(model);
                                    collection._byId[model.id] = model;
                                });
                            });

                            this.listenTo(collection, 'after:save after:remove', () => {
                                this.model.trigger('channelProductAttributeValuesChange');
                            });

                            $(this.options.el).append(`<div class="list-container" data-id="${channel.channelId}"></div>`);

                            if (!this.readOlny && !this.defs.readOnly) {
                                if (!('create' in this.defs)) {
                                    this.defs.create = true;
                                }
                                if (!('select' in this.defs)) {
                                    this.defs.select = true;
                                }
                            }

                            this.createView(`list-${channel.channelId}`, 'pim:views/channel-product-attribute-value/record/grouped-list', {
                                collection: collection,
                                el: `${this.options.el} .list-container[data-id="${channel.channelId}"]`,
                                type: 'list',
                                searchManager: this.searchManager,
                                listLayout: listLayout,
                                collectionLabel: channel.channelName,
                                channelId: channel.channelId,
                                create: this.defs.create && this.getAcl().check('ChannelProductAttributeValue', 'edit'),
                                select: this.defs.select && this.getAcl().check('ChannelProductAttributeValue', 'edit'),
                                rowActionsView: this.defs.readonly ? false : (this.defs.rowActionsView || this.rowActionsView)
                            }, function (view) {
                                view.render();
                            }, this);
                        });
                    }, this);
                });
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },

        showEmptyData() {
            this.$el.html(this.translate('No Data'));
        },

        selectChannelAttributes(channelId) {
            let viewName = 'pim:views/product/modals/select-channel-attributes';

            this.notify('Loading...');
            this.createView('selectChannelAttributes', viewName, {
                scope: 'Attribute',
                multiple: true,
                createButton: false,
                channelId: channelId,
                productId: this.model.id,
                channels: this.channels
            }, function (dialog) {
                this.listenTo(dialog, 'after:select', () => {
                    this.model.trigger('channelProductAttributeValuesChange');
                });
                dialog.render();
                this.notify(false);
            }.bind(this));
        },

        addChannelAttribute(channelId) {
            let viewName = 'pim:views/product/modals/add-channel-attribute';
            this.notify('Loading...');
            this.createView('addChannelAttribute', viewName, {
                scope: 'ChannelProductAttributeValue',
                channelId: channelId,
                channels: this.channels,
                productId: this.model.id
            }, function (dialog) {
                this.listenTo(dialog, 'after:save', () => {
                    this.model.trigger('channelProductAttributeValuesChange');
                });
                dialog.render();
                this.notify(false);
            }.bind(this));
        },

        actionRefresh: function () {
            this.buildChannels();
        },

    })
);