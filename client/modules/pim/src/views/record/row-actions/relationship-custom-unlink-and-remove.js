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

Espo.define('pim:views/record/row-actions/relationship-custom-unlink-and-remove', 'views/record/row-actions/default',
    Dep=> Dep.extend({

        getActionList: function () {
            let list = [];
            if (this.options.acl.edit) {
                list = list.concat([
                    {
                        action: 'unlinkRelatedCustom',
                        label: 'Unlink',
                        data: {
                            id: this.model.id
                        }
                    },
                    {
                        action: 'removeRelatedCustom',
                        label: 'Remove',
                        data: {
                            id: this.model.id
                        }
                    }
                ]);
            }
            return list;
        },

    })
);


