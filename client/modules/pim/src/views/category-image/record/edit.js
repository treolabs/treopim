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

Espo.define('pim:views/category-image/record/edit', 'views/record/edit',
    Dep => Dep.extend({

        isWide: true,

        sideViewDisabled: true,

        save(callback) {
            if (this.model.get('type') === 'Files') {
                this.multipleSave(callback);
            } else {
                this.singleSave(callback);
            }
        },

        singleSave(callback) {
            this.beforeBeforeSave();

            var data = this.fetch();

            var self = this;
            var model = this.model;

            var initialAttributes = this.attributes;

            var beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            var attrs = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (var name in data) {
                    if (_.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[name] = data[name];
                }
            }

            if (!attrs) {
                this.trigger('cancel:save');
                this.afterNotModified();
                return true;
            }

            model.set(attrs, {silent: true});

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    var isNew = self.isNew;
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    model.trigger('after:save');
                    this.trigger('after:save');

                    if (!callback) {
                        if (isNew) {
                            this.exit('create');
                        } else {
                            this.exit('save');
                        }
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    var r = xhr.getAllResponseHeaders();
                    var response = null;

                    if (xhr.status == 409) {
                        var header = xhr.getResponseHeader('X-Status-Reason');
                        try {
                            var response = JSON.parse(header);
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

        multipleSave(callback) {
            let data = {};
            let fetchData = this.fetch();
            let model = this.model;
            let beforeSaveAttributes = this.model.getClonedAttributes();
            let imagesIds = this.model.get('imagesMultipleIds');
            let promises = [];

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            data.categoriesIds = fetchData.categoriesIds;
            data.categoriesColumns = fetchData.categoriesColumns;
            data.state = this.model.get('state');
            data.type = "File";

            imagesIds.forEach((item) => {
                data.imageId = item;
                data.imageName = this.model.get('imagesMultipleNames')[item];
                promises.push(this.ajaxPostRequest(this.model.name, Espo.Utils.clone(data)).then(response => {
                    if (response.id && this.model.categoryId && this.model.get('scope') === 'Channel') {
                        let data = this.model.get('channelsIds');
                        promises.push(this.ajaxPutRequest(`CategoryImage/${response.id}/channels/${this.model.categoryId}`, data));
                    }
                }));
            });

            Promise.all(promises)
            .then(response => {
                this.afterSave();
                if (this.isNew) {
                    this.isNew = false;
                }
                this.trigger('after:save');
                this.model.trigger('after:save');

                if (!callback) {
                    this.exit('save');
                } else {
                    callback(this);
                }
            });
        }
    })
);