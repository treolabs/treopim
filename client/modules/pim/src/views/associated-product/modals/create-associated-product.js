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

Espo.define('pim:views/associated-product/modals/create-associated-product', 'pim:views/modals/edit-without-side',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'after:save', mainModel => {
                if (mainModel.get('bothDirections')) {
                    this.getModelFactory().create(mainModel.name, newModel => {
                        let attributes = mainModel.getClonedAttributes();
                        newModel.set({
                            associationId: attributes.backwardAssociationId,
                            associationName: attributes.backwardAssociationName,
                            mainProductId: attributes.relatedProductId,
                            mainProductName: attributes.relatedProductName,
                            relatedProductId: attributes.mainProductId,
                            relatedProductName: attributes.mainProductName
                        });
                        newModel.save().then(response => {
                            if (this.scope === this.getParentView().scope) {
                                this.getParentView().collection.fetch();
                            }
                        });
                    });
                }
            });
        }
    })
);

