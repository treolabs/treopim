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

Espo.define('pim:views/product/modals/select-entity-and-records', 'treo-core:views/modals/select-entity-and-records',
    Dep => Dep.extend({

        template: 'pim:product/modals/select-entity-and-records',

        validations: ['required'],

        setup() {
            Dep.prototype.setup.call(this);

            this.waitForView('association');
            this.createAssociationSelectView();
        },

        getDataForUpdateRelation(foreignIds, viewModel) {
            let data = Dep.prototype.getDataForUpdateRelation.call(this, foreignIds, viewModel);
            if (this.model.get('selectedLink') === 'associatedMainProducts') {
                data.associationId = viewModel.get('associationId');
            }
            return data;
        },

        createAssociationSelectView() {
            this.createView('association', 'pim:views/association/fields/backward-association', {
                el: `${this.options.el} .entity-container .field[data-name="association"]`,
                model: this.model,
                name: 'association',
                foreignScope: 'Association',
                inlineEditDisabled: true,
                mode: 'edit',
                defs: {
                    params: {
                        required: true
                    }
                },
                labelText: this.translate('association', 'fields', 'Product')
            }, view => {
                view.listenTo(view, 'after:render', () => {
                    this.checkScopeForAssociation();
                });
            });
        },

        reloadList(entity) {
            Dep.prototype.reloadList.call(this, entity);

            this.checkScopeForAssociation();
        },

        checkScopeForAssociation() {
            if (this.model.get('selectedLink') === 'associatedMainProducts') {
                this.getView('association').show();
            } else {
                this.getView('association').hide();
                this.model.set({
                    associationId: null,
                    associationName: null
                });
            }
        },

        getFieldViews() {
            let fields = {};
            if (this.hasView('association') && this.model.get('selectedLink') === 'associatedMainProducts') {
                fields['association'] = this.getView('association');
            }
            return fields;
        },

    })
);

