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

Espo.define('pim:views/fields/overview-channels-filter', 'treo-core:views/fields/dropdown-enum',
    Dep => Dep.extend({

        channels: [],

        optionsList: [
            {
                name: '',
                selectable: true
            },
            {
                name: 'onlyGlobalScope',
                selectable: true
            }
        ],

        relationships: ['productAttributeValues', 'productCategories', 'productImages'],

        setup() {
            this.baseOptionList = Espo.Utils.cloneDeep(this.optionsList);
            this.relationships = this.relationships.filter(name => {
                let foreignEntity = this.getMetadata().get(['entityDefs', 'Product', 'links', name, 'entity']);
                return foreignEntity && this.getAcl().check(foreignEntity, 'read');
            });
            this.wait(true);
            this.updateChannels(this.relationships, () => this.wait(false));

            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'attributes-updated categories-updated', () => {
                this.updateChannels(this.relationships, () => this.reRender());
            });
        },

        updateChannels(relationships, callback) {
            this.channels = [];
            this.optionsList = Espo.Utils.cloneDeep(this.baseOptionList);
            let count = 0;
            (relationships || []).forEach(name => {
                this.getFullEntityList(`Product/${this.model.id}/${name}`, {select: 'channelsIds,channelsNames'}, list => {
                    this.setChannelsFromList(list);
                    this.prepareOptionsList();
                    count++;
                    if (count === (relationships || []).length && callback) {
                        this.updateSelected();
                        this.modelKey = this.options.modelKey || this.modelKey;
                        this.setDataToModel({[this.name]: this.selected});
                        callback();
                    }
                });
            });
        },

        updateSelected() {
            if (this.storageKey) {
                let selected = ((this.getStorage().get(this.storageKey, this.scope) || {})[this.name] || {}).selected;
                if (this.optionsList.find(option => option.name === selected)) {
                    this.selected = selected;
                }
            }
            this.selected = this.selected || (this.optionsList.find(option => option.selectable) || {}).name;
        },

        getFullEntityList(url, params, callback, container) {
            if (url) {
                container = container || [];

                let options = params || {};
                options.maxSize = options.maxSize || 200;
                options.offset = options.offset || 0;

                this.ajaxGetRequest(url, options).then(response => {
                    container = container.concat(response.list || []);
                    options.offset = container.length;
                    if (response.total > container.length || response.total === -1) {
                        this.getFullEntity(url, options, callback, container);
                    } else {
                        callback(container);
                    }
                });
            }
        },

        setChannelsFromList(list) {
            list.forEach(item => {
                (item.channelsIds || []).forEach(channelId => {
                    if (!this.channels.find(channel => channel.id === channelId)) {
                        this.channels.push({
                            id: channelId,
                            name: (item.channelsNames || {})[channelId]
                        });
                    }
                });
            });
        },

        prepareOptionsList() {
            this.channels.forEach(channel => {
                if (!this.optionsList.find(option => option.name === channel.id)) {
                    this.optionsList.push({
                        name: channel.id,
                        label: channel.name,
                        selectable: true
                    });
                }
            });

            Dep.prototype.prepareOptionsList.call(this);
        }

    })
);