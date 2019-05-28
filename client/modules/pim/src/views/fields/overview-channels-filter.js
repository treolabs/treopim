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
            },
            {
                name: 'anyChannel',
                selectable: true
            }
        ],

        setup() {
            this.wait(true);
            this.getFullEntityList(`Product/${this.model.id}/productAttributeValues`, {select: 'channelsIds,channelsNames'}, list => {
                this.setChannelsFromList(list);
                this.prepareOptionsList();
                this.wait(false);
            });

            Dep.prototype.setup.call(this);
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
            list.forEach(attribute => {
                (attribute.channelsIds || []).forEach(channelId => {
                    if (!this.channels.find(item => item.id === channelId)) {
                        this.channels.push({
                            id: channelId,
                            name: (attribute.channelsNames || {})[channelId]
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