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

Espo.define('pim:views/product/record/panels/attributes', ['views/record/panels/relationship', 'views/record/panels/bottom'],
    (Dep, BottomPanel) => Dep.extend({

        allowedAttributeIds: [],

        pipelines: {
            setupPipes: ['clientDefs', 'Product', 'relationshipPanels', 'attributes', 'setupPipes']
        },

        setup() {
            let bottomPanel = new BottomPanel();
            bottomPanel.setup.call(this);

            this.link = this.link || this.defs.link || this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.title || this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            if (!this.getConfig().get('scopeColorsDisabled')) {
                var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
                if (iconHtml) {
                    if (this.defs.label) {
                        this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.scope);
                    } else {
                        this.titleHtml = iconHtml + this.title;
                    }
                }
            }

            let url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!this.readOnly && !this.defs.readOnly) {
                if (!('create' in this.defs)) {
                    this.defs.create = true;
                }
                if (!('select' in this.defs)) {
                    this.defs.select = true;
                }
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            if (this.defs.create) {
                if (this.getAcl().check('Attribute', 'create') && this.getAcl().check('ProductAttributeValue', 'create')) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'create',
                        aclScope: this.scope,
                        html: '<span class="fas fa-plus"></span>',
                        data: {
                            link: this.link,
                            fullFormDisabled: true
                        }
                    });
                }
            }

            if (this.defs.select && this.getAcl().check('ProductAttributeValue', 'create')) {
                let data = {link: this.link};
                if (this.defs.selectPrimaryFilterName) {
                    data.primaryFilterName = this.defs.selectPrimaryFilterName;
                }
                if (this.defs.selectBoolFilterList) {
                    data.boolFilterList = this.defs.selectBoolFilterList;
                }
                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: data
                });
            }

            this.runPipeline('setupPipes');

            this.once('after:render', () => {
                this.setupGrid();
            });

            this.setupFilterActions();

            this.listenTo(this.model, 'after:save', () => {
                this.actionRefresh();
            });
        },

        getFieldViews() {
            let result = null;
            let gridView = this.getView('grid');
            if (gridView) {
                result = {};
                Object.keys(gridView.nestedViews).forEach((item) => {
                    if (this.allowedAttributeIds.includes(item)) {
                        result[item] = gridView.nestedViews[item];
                    }
                });
            }
            return result;
        },

        setupGrid() {
            this.getModelFactory().create('productAttributesGrid', model => {
                let viewName = this.getMetadata().get('clientDefs.Product.relationshipPanels.attributes.gridView') || 'pim:views/attribute/grid';
                this.createView('grid', viewName, {
                    model: model,
                    parentModel: this.model,
                    gridLayout: [],
                    el: this.options.el + ' .list-container'
                }, function (view) {
                    view.listenTo(view, 'attributes-rendered', () => {
                        this.model.trigger('attributes-rendered');
                    });
                    view.listenTo(this.model, 'overview-filters-applied', () => {
                        this.checkGroupNamesVisibility();
                    });
                    this.listenTo(this.model, 'updateAttributes', () => {
                        this.updateGrid();
                    });
                    this.listenTo(model, 'updateAttributes', () => {
                        this.updateGrid();
                    });
                    view.render();
                    this.updateGrid();
                });
            });
        },

        checkGroupNamesVisibility() {
            let grid = this.getView('grid');
            if (grid) {
                (grid.gridLayout || []).forEach(group => {
                    let hideGroup = (group.rows || []).every(row => {
                        return (row || []).every(cell => {
                            let cellView = grid.getView(cell.name);
                            return !cellView || cellView.$el.hasClass('hidden');
                        });
                    });
                    if (hideGroup) {
                        grid.$el.find(`[data-group="${group.id}"]`).addClass('hidden');
                    } else {
                        grid.$el.find(`[data-group="${group.id}"]`).removeClass('hidden');
                    }
                });
            }
        },

        updateGrid() {
            let that = this;
            this.allowedAttributeIds = [];

            this.ajaxGetRequest(`Markets/Product/${this.model.id}/attributes`).then(function (response) {
                if (Array.isArray(response) && response) {
                    let data = {};
                    let translates = {};
                    let layout = [];
                    let defs = {
                        fields: {}
                    };
                    let inputLanguageList = that.getConfig().get('inputLanguageList');
                    let textTypes = ['text', 'textMultiLang', 'wysiwyg', 'wysiwygMultiLang'];

                    let notTextFields = response.filter(item => !textTypes.includes(item.type));
                    notTextFields.sort((a, b) => a.sortOrder - b.sortOrder);
                    let textFields = response.filter(item => textTypes.includes(item.type));
                    textFields.sort((a, b) => a.sortOrder - b.sortOrder);

                    response = [].concat(notTextFields, textFields);

                    response.forEach(attribute => {
                        let multilangLabels = {};
                        this.allowedAttributeIds.push(attribute.attributeId);
                        data[attribute.attributeId] = attribute.value;
                        translates[attribute.attributeId] = attribute.name;

                        if (Espo.Utils.isObject(attribute.data)) {
                            Object.keys(attribute.data).forEach(param => data[`${attribute.attributeId}${Espo.Utils.upperCaseFirst(param)}`] = attribute.data[param]);
                        }

                        defs.fields[attribute.attributeId] = {
                            type: attribute.type,
                            required: attribute.isRequired,
                            options: attribute.typeValue,
                            readOnly: !attribute.editable,
                            isMultilang: ['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang', 'wysiwygMultiLang'].includes(attribute.type)
                        };

                        if (attribute.type === 'unit') {
                            defs.fields[attribute.attributeId].measure = (attribute.typeValue || [])[0];
                        }

                        if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang', 'wysiwygMultiLang'].includes(attribute.type)) {
                            if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                                inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''))
                                    .forEach(item => {
                                        data[`${attribute.attributeId}${item}`] = attribute[`value${item}`];
                                        defs.fields[attribute.attributeId][`options${item}`] = attribute[`typeValue${item}`];
                                        multilangLabels[`${attribute.attributeId}${item}`] = attribute[`name${item}`];
                                    });
                            }
                        }

                        let group = layout.find(item => item.id === attribute.attributeGroupId);
                        if (!group) {
                            group = {
                                id: attribute.attributeGroupId,
                                label: attribute.attributeGroupName,
                                order: attribute.attributeGroupOrder,
                                rows: []
                            };
                            layout.push(group);
                        }
                        let item = {
                            name: attribute.attributeId,
                            defs: defs.fields[attribute.attributeId],
                            label: attribute.name,
                            multilangLabels: multilangLabels,
                            editable: attribute.editable,
                            deletable: attribute.isCustom && attribute.deletable,
                            oneInRow: textTypes.includes(attribute.type)
                        };
                        if (!textTypes.includes(attribute.type) && group.rows.length && group.rows[group.rows.length - 1].length === 1) {
                            group.rows[group.rows.length - 1].push(item);
                        } else {
                            group.rows.push([item]);
                        }
                    });

                    layout.sort((first, second) => {
                        return (first.order < second.order) ? -1 : 1;
                    });

                    this.getLanguage().data['productAttributesGrid'] = {fields: translates};

                    let grid = that.getView('grid');
                    if (grid) {
                        for (let key in grid.nestedViews) {
                            grid.clearView(key);
                        }
                        grid.model.setDefs(defs);
                        grid.initialData = Espo.Utils.cloneDeep(response);
                        grid.attributes = Espo.Utils.cloneDeep(data);
                        grid.model.set(data);
                        grid.gridLayout = layout;

                        grid.reRender();
                    }
                }
            });
        },

        getDetailView() {
            let panelView = this.getParentView();
            if (panelView) {
                return panelView.getParentView()
            }
            return null;
        },

        getInitAttributes() {
            return this.getView('grid').attributes || [];
        },

        checkAttributeChanges(values) {
            let check = false;
            let prev = this.getInitAttributes();
            if (Espo.Utils.isObject(values)) {
                check = Object.keys(values).some(item => !_.isEqual(prev[item], values[item]));
            }
            return check;
        },

        save() {
            let inputLanguageList = (this.getConfig().get('inputLanguageList') || [])
                .map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
            let data = [];
            let fields = this.getFieldViews();
            for (let i in fields) {
                let field = fields[i];
                if (!field.disabled && !field.readOnly) {
                    let fieldValue = field.fetch();
                    if (this.checkAttributeChanges(fieldValue)) {
                        let item = {
                            attributeId: field.name,
                            value: fieldValue[field.name],
                        };
                        let additionalData = this.getAdditionalFieldData(field, fieldValue);
                        if (additionalData) {
                            item.data = additionalData;
                        }
                        inputLanguageList.forEach(lang => item[`value${lang}`] = fieldValue[`${field.name}${lang}`] || null);
                        data.push(item);
                    }
                }
            }

            if (!data.length) {
                return;
            }

            this.ajaxPutRequest(`Markets/Product/${this.model.id}/attributes`, data)
                .then(response => {
                    this.updateGrid();
                    this.model.trigger('after:attributesSave');
                    this.notify('Saved', 'success');
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

        cancelEdit() {
            let gridView = this.getView('grid');
            if (gridView) {
                gridView.model.set(gridView.attributes);
            }
        },

        actionRefresh: function () {
            this.updateGrid();
        },

    })
);