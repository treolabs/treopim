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

Espo.define('pim:views/product/record/detail', 'pim:views/record/detail',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            if (!this.model.isNew() && (this.type === 'detail' || this.type === 'edit') && this.getMetadata().get(['scopes', this.scope, 'advancedFilters'])) {
                this.listenTo(this.model, 'attributes-rendered main-image-updated', () => {
                    this.applyOverviewFilters();
                });
            }
        },

        hotKeySave: function (e) {
            e.preventDefault();
            if (this.mode === 'edit') {
                this.actionSave();
            } else {
                let viewsFields = this.getFieldViews();
                Object.keys(viewsFields).forEach(item => {
                    if (viewsFields[item].mode === "edit" ) {
                        if (viewsFields[item].model.name === 'productAttributesGrid') {
                            viewsFields[item].getParentView().inlineEditSave(viewsFields[item]);
                        } else {
                            viewsFields[item].inlineEditSave();
                        }
                    }
                });
            }
        },

        cancelEdit() {
            let bottomView = this.getView('bottom');
            if (bottomView) {
                for (let panel in bottomView.nestedViews) {
                    if (typeof bottomView.nestedViews[panel].cancelEdit === 'function') {
                        bottomView.nestedViews[panel].cancelEdit();
                    }
                }
            }
            Dep.prototype.cancelEdit.call(this);
        },

        afterNotModified(notShow) {
            if (!notShow) {
                let msg = this.translate('notModified', 'messages');
                Espo.Ui.warning(msg, 'warning');
            }
            this.enableButtons();
        },

        save(callback, skipExit) {
            this.beforeBeforeSave();

            let data = this.fetch();

            let self = this;
            let model = this.model;

            let initialAttributes = this.attributes;

            let beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            let gridInitAttrs = false;
            let gridInitPackages = false;
            let attributesView = false;
            let packageView = false;
            let bottomView = this.getView('bottom');
            if (bottomView) {
                attributesView = bottomView.getView('attributes');
                packageView = bottomView.getView('productTypePackages');
                if (attributesView) {
                    gridInitAttrs = attributesView.getInitAttributes();
                }
                if (packageView) {
                    gridInitPackages = packageView.getInitAttributes();
                }
            }

            let attrs = false;
            let gridAttrs = false;
            let gridPackages = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (let name in data) {
                    if (name !== 'id'&& gridInitAttrs && Object.keys(gridInitAttrs).indexOf(name) > -1) {
                        if (data[name] === '') {
                            data[name] = null;
                        }
                        if (!_.isEqual(gridInitAttrs[name], data[name])) {
                            (gridAttrs || (gridAttrs = {}))[name] = data[name];
                        }
                        continue;
                    }

                    if (name !== 'id'&& gridInitPackages && Object.keys(gridInitPackages).indexOf(name) > -1) {
                        if (!_.isEqual(gridInitPackages[name], data[name])) {
                            (gridPackages || (gridPackages = {}))[name] = data[name];
                        }
                        continue;
                    }

                    if (_.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[name] = data[name];
                }
            }

            model.set(attrs, {silent: true});

            let beforeSaveGridAttrs = false;
            if (gridAttrs && attributesView) {
                let gridModel = attributesView.getView('grid').model;
                beforeSaveGridAttrs = gridModel.getClonedAttributes();
                gridModel.set(gridAttrs, {silent: true})
            }

            let beforeSaveGridPackages = false;
            if (gridPackages && packageView) {
                let gridModel = packageView.getView('grid').model;
                beforeSaveGridPackages = gridModel.getClonedAttributes();
                gridModel.set(gridPackages, {silent: true})
            }


            let productFamilyChanged = attrs && ('productFamilyId' in attrs);

            if (this.validate(productFamilyChanged)) {
                if (gridAttrs && attributesView && beforeSaveGridAttrs) {
                    attributesView.getView('grid').model.attributes = beforeSaveGridAttrs;
                }

                if (gridPackages && packageView && beforeSaveGridPackages) {
                    packageView.getView('grid').model.attributes = beforeSaveGridPackages;
                }

                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            if (gridAttrs && attributesView && !productFamilyChanged) {
                attributesView.save();
            }

            if (gridPackages && packageView) {
                packageView.save();
            }

            if (!attrs) {
                this.afterNotModified(gridAttrs || gridPackages);
                this.trigger('cancel:save');
                return true;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    model.trigger('after:save');

                    if (!callback) {
                        if (!skipExit) {
                            if (self.isNew) {
                                this.exit('create');
                            } else {
                                this.exit('save');
                            }
                        }
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    let r = xhr.getAllResponseHeaders();
                    let response = null;

                    if (xhr.status == 409) {
                        let header = xhr.getResponseHeader('X-Status-Reason');
                        try {
                            let response = JSON.parse(header);
                        } catch (e) {
                            console.error('Error while parsing response');
                        }
                    }

                    if (xhr.status == 400) {
                        if (!this.isNew) {
                            this.model.set(this.attributes);
                        }
                    }

                    if (response) {
                        if (response.reason == 'Duplicate') {
                            xhr.errorIsHandled = true;
                            self.showDuplicate(response.data);
                        }
                    }

                    this.afterSaveError();

                    model.attributes = beforeSaveAttributes;
                    self.trigger('cancel:save');

                }.bind(this),
                patch: !model.isNew()
            });
            return true;
        },

        validate(skipAttributes) {
            let notValid = false;
            let fields = this.getFields();

            if (skipAttributes) {
                let attributeFields = {};
                let bottomView = this.getView('bottom');
                if (bottomView) {
                    let attributesView = bottomView.getView('attributes');
                    if (attributesView) {
                        attributeFields = attributesView.getFieldViews();
                    }
                }
                for (let key in attributeFields) {
                    delete fields[key];
                }
            }

            for (let i in fields) {
                if (fields[i].mode == 'edit') {
                    if (!fields[i].disabled && !fields[i].readOnly) {
                        notValid = fields[i].validate() || notValid;
                    }
                }
            };
            return notValid
        }
    })
);

