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

Espo.define('pim:controllers/product', 'controllers/record', Dep => Dep.extend({

    defaultAction: 'list',

    beforePlate() {
        this.handleCheckAccess('read');
    },

    plate() {
        this.getCollection(function (collection) {
            this.main(this.getViewName('plate'), {
                scope: this.name,
                collection: collection
            });
        });
    },

    list(options) {
        var callback = options.callback;
        var isReturn = options.isReturn;
        if (this.getRouter().backProcessed) {
            isReturn = true;
        }

        var key = this.name + 'List';

        if (!isReturn) {
            var stored = this.getStoredMainView(key);
            if (stored) {
                this.clearStoredMainView(key);
            }
        }

        this.getCollection(function (collection) {
            this.listenToOnce(this.baseController, 'action', function () {
                collection.abortLastFetch();
            }, this);

            this.main(this.getViewName('list'), {
                scope: this.name,
                collection: collection,
                params: options
            }, callback, isReturn, key);
        }, this, false);
    }

}));
