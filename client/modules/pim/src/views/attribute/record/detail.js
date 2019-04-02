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

Espo.define('pim:views/attribute/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        delete: function () {
            Espo.TreoUi.confirmWithBody('', {
                confirmText: this.translate('Remove'),
                cancelText: this.translate('Cancel'),
                body: this.getBodyHtml()
            }, function () {
                this.trigger('before:delete');
                this.trigger('delete');

                this.notify('Removing...');

                var collection = this.model.collection;

                var self = this;
                this.model.destroy({
                    wait: true,
                    data: JSON.stringify({force: $('.force-remove').is(':checked')}),
                    error: function () {
                        this.notify('Error occured!', 'error');
                    }.bind(this),
                    success: function () {
                        if (collection) {
                            if (collection.total > 0) {
                                collection.total--;
                            }
                        }

                        this.notify('Removed', 'success');
                        this.trigger('after:delete');
                        this.exit('delete');
                    }.bind(this),
                });
            }, this);
        },

        getBodyHtml() {
            return '' +
                '<div class="row">' +
                    '<div class="col-xs-12">' +
                        '<span class="confirm-message">' + this.translate('removeRecordConfirmation', 'messages') + '</span>' +
                    '</div>' +
                    '<div class="col-xs-12">' +
                        '<div class="cell pull-left" style="margin-top: 15px;">' +
                            '<input type="checkbox" class="force-remove"> ' +
                            '<label class="control-label">' + this.translate('removeExplain', 'labels', 'Attribute') + '</label>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }

    })
);