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

Espo.define('pim:views/attribute/grid', 'views/base',
    Dep => Dep.extend({

        template: 'pim:attribute/grid',

        attributeValueModalView: 'pim:views/attribute/modals/attribute-value',

        gridLayout: null,

        mode: 'detail',

        pipelines: {
            productAttributesPipe: ['clientDefs', 'Product', 'productAttributesPipe']
        },

        events: _.extend({
            'click .inline-remove-link': function (e) {
                this.actionRemoveAttribute($(e.currentTarget).data('name'));
            },
            'click .show-attribute-value-modal': function (e) {
                this.actionShowAttributeValueModal($(e.currentTarget).data('name'));
            }
        }, Dep.prototype.events),

        setup() {
            Dep.prototype.setup.call(this);

            this.runPipeline('productAttributesPipe');

            this.gridLayout = this.options.gridLayout;
        },

        data() {
            return {gridLayout: this.gridLayout} || [];
        },

        afterRender() {
            this.buildGrid();

            Dep.prototype.afterRender.call(this);
        },

        buildGrid() {
            if (this.nestedViews) {
                for (let child in this.nestedViews) {
                    this.clearView(child);
                }
            }

            let mode = this.getDetailViewMode();

            let viewCount = 0;
            this.gridLayout.forEach(panel => {
                panel.rows.forEach(row => {
                    row.forEach(cell => {
                        let fieldDefs = cell.defs;
                        let viewName = fieldDefs.type !== 'bool' ? this.getFieldManager().getViewName(fieldDefs.type) : 'pim:views/fields/bool-required';
                        let options = {
                            mode: mode,
                            inlineEditDisabled: true,
                            model: this.model,
                            el: `${this.options.el} .field[data-name="${cell.name}"]`,
                            customLabel: cell.label,
                            multilangLabels: cell.multilangLabels,
                            editable: cell.editable,
                            deletable: cell.deletable,
                            defs: {
                                name: cell.name,
                            },
                            params: {
                                required: fieldDefs.required
                            }
                        };
                        this.prepareCellViewOptions(options, cell);
                        this.createView(cell.name, viewName, options, (view) => {
                            view.listenToOnce(view, 'after:render', this.initInlineActions, view);

                            view.listenTo(view, 'edit', function () {
                                let fields = this.getParentView().nestedViews;
                                for (let field in fields) {
                                    if (fields[field] && fields[field].mode === 'edit' && fields[field] !== view && field !== 'productAttributeValueModal') {
                                        this.getParentView().inlineCancelEdit(fields[field]);
                                    }
                                }
                            }, view);
                            view.listenTo(view, 'after:render', () => {
                                viewCount++;
                                if (viewCount === (this.initialData || []).length) {
                                    this.trigger('attributes-rendered');
                                }
                            });
                            view.render();
                        });
                    }, this);
                }, this);
            }, this)
        },

        prepareCellViewOptions(options, cell) {},

        actionRemoveAttribute(id) {
            if (!id) {
                return;
            }
            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                let productId = this.options.parentModel.id;
                $.ajax({
                    url: `Product/${productId}/attributes`,
                    data: JSON.stringify({id: id}),
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.model.trigger('updateAttributes');
                        this.notify('Unlinked', 'success');
                        this.options.parentModel.trigger('after:unrelate', 'attributes');
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        initInlineActions() {
            let cell = this.getCellElement();
            let editLink = cell.find('.inline-edit-link');
            let removeLink = cell.find('.inline-remove-link');
            let saveLink = cell.find('.inline-save-link');
            let cancelLink = cell.find('.inline-cancel-link');

            if (cell.size() === 0) {
                return;
            }

            editLink.on('click', () => {
                this.trigger('edit');
                this.setMode('edit');
                this.inlineEditInProgress = true;
                this.initialAttributes = this.model.getClonedAttributes();
                this.getParentView().hideInlineLinks(this);
                this.reRender();
                this.trigger('inline-edit-on');
            });
            saveLink.on('click', () => {
                this.getParentView().inlineEditSave(this);
            });
            cancelLink.on('click', () => {
                this.getParentView().inlineCancelEdit(this);
            });

            cell.on('mouseenter', e => {
                if (!cell.hasClass('hidden-cell') && !cell.hasClass('hidden')) {
                    e.stopPropagation();
                    if (this.disabled || this.readOnly) {
                        return;
                    }
                    if (this.mode === 'detail') {
                        editLink.removeClass('hidden');
                    }
                    if (!this.inlineEditInProgress) {
                        removeLink.removeClass('hidden');
                    }
                }
            }).on('mouseleave', e => {
                if (!cell.hasClass('hidden-cell') && !cell.hasClass('hidden')) {
                    e.stopPropagation();
                    if (this.mode === 'detail') {
                        editLink.addClass('hidden');
                    }
                    if (!this.inlineEditInProgress) {
                        removeLink.addClass('hidden');
                    }
                }
            });
        },

        getAdditionalFieldData(view, data) {
            let additionalData = false;
            if (view.type === 'unit') {
                let actualFieldDefs = this.getMetadata().get(['fields', view.type, 'actualFields']) || [];
                let actualFieldValues = this.getFieldManager().getActualAttributes(view.type, view.name) || [];
                actualFieldDefs.forEach((field, i) => {
                    if (field) {
                        additionalData = additionalData || {};
                        additionalData[field] = data[actualFieldValues[i]];
                    }
                });
            }
            return additionalData;
        },

        inlineEditSave: function (view) {
            let data = view.fetch();
            let prev = view.initialAttributes;

            view.model.set(data, {silent: true});
            let dataToSave = [];
            let inputLanguageList = (this.getConfig().get('inputLanguageList') || [])
                .map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
            let item = {
                attributeId: view.name,
                value: data[view.name],
            };
            inputLanguageList.forEach(lang => item[`value${lang}`] = data[`${view.name}${lang}`] || null);
            let additionalData = this.getAdditionalFieldData(view, data);
            if (additionalData) {
                item.data = additionalData;
            }
            dataToSave.push(item);

            let attrs = false;
            for (let attr in data) {
                if (data[attr] === '') {
                    data[attr] = null;
                }
                if (_.isEqual(prev[attr], data[attr])) {
                    continue;
                }
                (attrs || (attrs = {}))[attr] = data[attr];
            }

            if (!attrs) {
                this.inlineCancelEdit(view);
                return;
            }

            if (view.validate()) {
                view.notify('Not valid', 'error');
                return;
            }

            this.notify('Saving...');
            this.ajaxPutRequest(`Markets/Product/${this.options.parentModel.id}/attributes`, dataToSave)
                .then(response => {
                    this.model.trigger('updateAttributes');
                    this.options.parentModel.trigger('after:attributesSave');
                    this.notify('Saved', 'success');
                    this.inlineCancelEdit(view, true);
                });
        },

        inlineCancelEdit(view, dontReset) {
            view.trigger('inline-edit-off');
            view.setMode('detail');
            view.inlineEditInProgress = false;
            this.showInlineLinks(view);
            if (!dontReset) {
                view.model.set(view.initialAttributes);
            }
            view.reRender();
        },

        hideInlineLinks(view) {
            let cell = view.getCellElement();
            cell.find('.inline-edit-link').addClass('hidden');
            cell.find('.inline-remove-link').addClass('hidden');
            cell.find('.inline-save-link').removeClass('hidden');
            cell.find('.inline-cancel-link').removeClass('hidden');
        },

        showInlineLinks(view) {
            let cell = view.getCellElement();
            cell.find('.inline-edit-link').removeClass('hidden');
            cell.find('.inline-remove-link').removeClass('hidden');
            cell.find('.inline-save-link').addClass('hidden');
            cell.find('.inline-cancel-link').addClass('hidden');
        },

        getDetailViewMode() {
            let mode = 'detail';
            let parentView = this.getParentView();
            if (parentView) {
                let detailView = this.getParentView().getDetailView();
                if (detailView) {
                    mode = detailView.mode;
                }
            }
            return mode;
        },

        actionShowAttributeValueModal(name) {
            let attributeValueData = Espo.Utils.cloneDeep(this.initialData || []).find(item => item.attributeId === name);
            let options = {
                name: name,
                model: this.model,
                attributeValueData: {
                    assignedUserId: attributeValueData.assignedUserId,
                    assignedUserName: attributeValueData.assignedUserName,
                    ownerUserId: attributeValueData.ownerUserId,
                    ownerUserName: attributeValueData.ownerUserName,
                    teamsIds: attributeValueData.teamsIds,
                    teamsNames: attributeValueData.teamsNames
                },
                parentModel: this.options.parentModel
            };
            this.prepareAttributeValueModalOptions(options, attributeValueData);
            this.createView('productAttributeValueModal', this.attributeValueModalView, options, view => {
                view.render();
            });
        },

        prepareAttributeValueModalOptions(options, attributeValueData) {}

    })
);
