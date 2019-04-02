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

Espo.define('pim:views/dashlets/fields/varchar-with-url', 'views/fields/varchar',
    Dep => Dep.extend({

        listTemplate: 'pim:dashlets/fields/varchar-with-url/list',

        events: {
            'click a': function (event) {
                event.stopPropagation();
                event.preventDefault();
                let hash = event.currentTarget.hash;
                let name = this.model.get(this.name);
                let options = ((this.model.getFieldParam(this.name, 'urlMap') || {})[name] || {}).options;
                this.getRouter().navigate(hash, {trigger: false});
                this.getRouter().dispatch(hash.substr(1), 'list', options);
            }
        },

        data() {
            let name = this.model.get(this.name);
            let url = ((this.model.getFieldParam(this.name, 'urlMap') || {})[name] || {}).url;

            return {
                hasUrl: !!url,
                label: (this.model.getFieldParam(this.name, 'labelMap') || {})[name] || name,
                url: url
            }
        }

    })
);

