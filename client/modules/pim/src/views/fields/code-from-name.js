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

Espo.define('pim:views/fields/code-from-name', 'pim:views/fields/varchar-with-pattern',
    Dep => Dep.extend({

        validationPattern: '^[a-z_0-9{}\u00de-\u00ff]+$',

        getPatternValidationMessage() {
            return this.translate('fieldHasPattern', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:name', () => {
                if (!this.model.get('code')) {
                    let value = this.model.get('name');
                    if (value) {
                        this.model.set({[this.name]: this.transformToPattern(value)});
                    }
                }
            });
        },

        transformToPattern(value) {
            return value.toLowerCase().replace(/ /g, '_').replace(/[^a-z_0-9\u00de-\u00ff]/gu, '');
        }

    })
);
