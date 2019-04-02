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

Espo.define('pim:views/dashlets/channels', 'views/dashlets/abstract/base',
    Dep => Dep.extend({

        _template: '<div class="list-container">{{{list}}}</div>',

        collectionUrl: 'Dashlet/Channels',

        actionRefresh: function () {
            this.collection.fetch();
        },

        afterRender: function () {
            this.getCollectionFactory().create('ChannelsDashlet', function (collection) {
                this.collection = collection;

                collection.url = this.collectionUrl;
                collection.maxSize = this.getOption('displayRecords');
                collection.model = collection.model.extend({
                    defs: {
                        fields: {
                            name: {
                                linkEntity: 'Channel'
                            },
                            catalog: {
                                entity: 'Catalog'
                            }
                        }
                    }
                });

                this.listenToOnce(collection, 'sync', function () {
                    this.createView('list', 'views/record/list', {
                        el: this.getSelector() + ' > .list-container',
                        collection: collection,
                        rowActionsDisabled: true,
                        checkboxes: false,
                        listLayout: [
                            {
                                name: 'name',
                                link: true,
                                notSortable: true,
                                width: '26',
                                view: 'pim:views/dashlets/fields/list-link-extended'
                            },
                            {
                                name: 'catalog',
                                type: "link",
                                notSortable: true,
                                width: '26'
                            },
                            {
                                name: 'products',
                                notSortable: true,
                                width: '16'
                            },
                            {
                                name: 'active',
                                notSortable: true,
                                width: '16'
                            },
                            {
                                name: 'notActive',
                                notSortable: true,
                                width: '16'
                            }
                        ]
                    }, view => {
                        view.listenTo(view, 'after:render', () => {
                            let products = 0
                            let active = 0;
                            let notActive = 0;
                            collection.each(model => {
                                products += model.get('products');
                                active += model.get('active');
                                notActive += model.get('notActive');
                            });
                            view.$el.find('table.table tbody').append(
                                `<tr data-id="total" class="list-row">
                                    <td class="cell" data-name="name" width="26%"><b>${this.translate('Total', 'labels', 'Global')}</b></td>
                                    <td class="cell" data-name="catalog" width="26%"></td>
                                    <td class="cell" data-name="products" width="16%"><b>${products}</b></td>
                                    <td class="cell" data-name="active" width="16%"><b>${active}</b></td>
                                    <td class="cell" data-name="notActive" width="16%"><b>${notActive}</b></td>
                                </tr>'`
                            );
                        });

                        view.render();
                    });
                }.bind(this));
                collection.fetch();

            }, this);
        },

    })
);

