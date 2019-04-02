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

Espo.define('pim:views/fields/image-code-name', 'pim:views/fields/varchar-with-pattern',
    Dep => Dep.extend({

        validationPattern: '^[a-z_0-9]+$',

        getPatternValidationMessage() {
            return this.translate('fieldHasPattern', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
        },

        setup() {
            Dep.prototype.setup.call(this);

            if (this.model.isNew()) {
                this.listenTo(this.model, 'change:imageName', () => {
                    if (!this.model.get(this.name)) {
                        let value = this.model.get('imageName');
                        if (value) {
                            this.model.set({[this.name]: this.transformToPattern(this.removeExtension(value))});
                        }
                    }
                });
            }
        },

        removeExtension(value) {
            return value.replace(/\.[a-zA-Z0-9]+$/g, '');
        },

        transformToPattern(value) {
            return value.toLowerCase().replace(/ /g, '_').replace(/[^a-z_0-9]/g, '');
        }

    })
);
