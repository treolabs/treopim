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

Espo.define('pim:views/product/record/panels/product-channels', 'views/record/panels/relationship',
    Dep => Dep.extend({
        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.collection.parentEntityId = this.model.get('id');

            this.listenTo(this.collection, 'change:isActiveEntity', model => {
                if (!model.hasChanged('modifiedAt')) {
                    this.notify('Saving...');
                    let value = model.get('isActiveEntity');
                    let data = {entityName: 'Product', value: value, entityId: this.collection.parentEntityId};
                    this.ajaxPutRequest('Channel/' + model.get('id') + '/setIsActiveEntity', data).then(response => {
                        this.notify('Saved', 'success');
                    });
                }
            });
        },
    })
);