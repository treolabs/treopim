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

Espo.define('pim:views/product/actions/action-mass-update-attributes', 'view',
    Dep => Dep.extend({

        actionMassUpdateAttributes: function () {
            if (!this.getAcl().check(this.options.scope, 'edit') && this.getAcl().check('Attribute', 'edit')) {
                this.notify('Access denied', 'error');
                return false;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));
            let checkedIds = false;
            if (!this.options.allResultIsChecked) {
                checkedIds = this.options.checkedList;
            }

            this.createView('mass-update-attributes', 'pim:views/product/modals/mass-update-attributes', {
                scope: this.options.scope,
                ids: checkedIds,
                where: this.options.collection.getWhere(),
                selectData: this.options.collection.data,
                byWhere: this.options.allResultIsChecked
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:update', function (count, byQueueManager) {
                    view.close();
                    if (count) {
                        let msg = 'massUpdateResult';
                        if (count == 1) {
                            msg = 'massUpdateResultSingle'
                        }
                        this.notify(this.translate(msg, 'messages').replace('{count}', count), 'success');
                    } else if (byQueueManager) {
                        this.notify(this.translate('byQueueManager', 'messages', 'QueueItem'), 'success');
                        Backbone.trigger('showQueuePanel');
                    } else {
                        this.notify(this.translate('noRecordsUpdated', 'messages'), 'warning');
                    }
                }, this);
            }.bind(this));
        },
    })
);

