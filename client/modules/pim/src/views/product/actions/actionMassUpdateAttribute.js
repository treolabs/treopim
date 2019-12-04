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

Espo.define('pim:views/product/actions/actionMassUpdateAttribute', 'view',
    Dep => Dep.extend({

        actionMassUpdateAttribute: function () {
            if (!this.getAcl().check(this.options.scope, 'edit')) {
                this.notify('Access denied', 'error');
                return false;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));
            let checkedIds = false;
            if (!this.options.allResultIsChecked) {
                checkedIds = this.options.checkedList;
            }

            this.createView('massUpdateAttribute', 'pim:views/product/modals/massUpdateAttribute', {
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
                    this.listenToOnce(this.options.collection, 'sync', function () {
                        if (count) {
                            var msg = 'massUpdateResult';
                            if (count == 1) {
                                msg = 'massUpdateResultSingle'
                            }
                            Espo.Ui.success(this.translate(msg, 'messages').replace('{count}', count));
                        } else if (byQueueManager) {
                            Espo.Ui.success(this.translate('byQueueManager', 'messages', 'QueueItem'));
                            Backbone.trigger('showQueuePanel');
                        } else {
                            Espo.Ui.warning(this.translate('noRecordsUpdated', 'messages'));
                        }
                        if (this.options.allResultIsChecked) {
                            this.selectAllResult();
                        } else {
                            checkedIds.forEach(function (id) {
                                this.checkRecord(id);
                            }, this);
                        }
                    }.bind(this));
                }, this);
            }.bind(this));
        },
    })
);

