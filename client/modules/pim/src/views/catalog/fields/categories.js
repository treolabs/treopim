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

Espo.define('pim:views/catalog/fields/categories', 'treo-core:views/fields/filtered-link-multiple',
    Dep => Dep.extend({

        createDisabled: true,

        selectBoolFilterList:  ['notEntity', 'onlyRootCategory'],

        boolFilterData: {
            notEntity() {
                return this.ids || [];
            },
            onlyRootCategory() {
                return true;
            }
        },

    })
);
