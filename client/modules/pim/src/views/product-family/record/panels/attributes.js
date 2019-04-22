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

Espo.define('pim:views/product-family/record/panels/attributes', ['views/record/panels/relationship', 'views/record/panels/bottom', 'search-manager'],
    (Dep, BottomPanel, SearchManager) => Dep.extend({

        template: 'pim:product-family/record/panels/attributes',

        groupKey: 'attributeGroupId',

        groupLabel: 'attributeGroupName',

        groupScope: 'AttributeGroup',

        noGroup: {
            key: 'no_group',
            label: 'No Group'
        },

        boolFilterData: {
            notLinkedWithProductFamily() {
                return this.model.id;
            }
        },

        events: _.extend({
            'click [data-action="unlinkAttributeGroup"]': function(e) {
                e.preventDefault();
                e.stopPropagation();
                let data = $(e.currentTarget).data();
                this.unlinkAttributeGroup(data);
            }
        }, Dep.prototype.events),

        data() {
            return _.extend({
                groups: this.groups,
                groupScope: this.groupScope
            }, Dep.prototype.data.call(this));
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

            var url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

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

            if (this.getAcl().check('Attribute', 'create') && !~['User', 'Team'].indexOf()) {
                this.buttonList.push({
                    title: 'Create',
                    action: this.defs.createAction || 'createRelated',
                    link: this.link,
                    acl: 'create',
                    aclScope: this.scope,
                    html: '<span class="fas fa-plus"></span>',
                    data: {
                        link: this.link,
                    }
                });
            }

            if (this.defs.select) {
                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: {
                        link: this.link,
                        boolFilterListCallback: 'getSelectBoolFilterList',
                        boolFilterDataCallback: 'getSelectBoolFilterData',
                        boolFilterList: this.defs.selectBoolFilterList,
                        primaryFilterName: this.defs.selectPrimaryFilterName || null
                    },
                    acl: 'edit',
                    aclScope: this.model.name
                });
            }

            this.setupActions();

            var layoutName = 'listSmall';
            this.setupListLayout();

            if (this.listLayoutName) {
                layoutName = this.listLayoutName;
            }

            var listLayout = null;
            var layout = this.defs.layout || null;
            if (layout) {
                if (typeof layout == 'string') {
                    layoutName = layout;
                } else {
                    layoutName = 'listRelationshipCustom';
                    listLayout = layout;
                }
            }

            this.layoutName = layoutName;
            this.listLayout = listLayout;

            var sortBy = this.defs.sortBy || null;
            var asc = this.defs.asc || null;

            if (this.defs.orderBy) {
                sortBy = this.defs.orderBy;
                asc = true;
                if (this.defs.orderDirection) {
                    if (this.defs.orderDirection && (this.defs.orderDirection === true || this.defs.orderDirection.toLowerCase() === 'DESC')) {
                        asc = false;
                    }
                }
            }

            this.wait(true);
            this.getCollectionFactory().create(this.scope, function (collection) {
                collection.maxSize = 200;

                if (this.defs.filters) {
                    var searchManager = new SearchManager(collection, 'listRelationship', false, this.getDateTime());
                    searchManager.setAdvanced(this.defs.filters);
                    collection.where = searchManager.getWhere();
                }

                collection.url = collection.urlRoot = url;
                if (sortBy) {
                    collection.sortBy = sortBy;
                }
                if (asc) {
                    collection.asc = asc;
                }
                this.collection = collection;

                this.setFilter(this.filter);

                this.listenTo(this.model, 'update-all after:relate after:unrelate', () => {
                    this.actionRefresh();
                });

                this.listenTo(collection, 'change:isRequired change:isMultiChannel', model => {
                    this.notify('Saving...');
                    this.ajaxPutRequest('ProductFamily/action/updateAttribute', {
                        attributeId: model.id,
                        productFamilyId: this.model.id,
                        ...model.changed
                    }).then(() => this.notify('Saved', 'success'));
                });

                this.fetchCollectionGroups(() => this.wait(false));
            }, this);

            this.setupFilterActions();
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.buildGroups();
        },

        fetchCollectionGroups(callback) {
            this.getHelper().layoutManager.get(this.scope, this.layoutName, layout => {
                let list = [];
                layout.forEach(item => {
                    if (item.name) {
                        let field = item.name;
                        let fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', field, 'type']);
                        if (fieldType) {
                            this.getFieldManager().getAttributeList(fieldType, field).forEach(attribute => {
                                list.push(attribute);
                            });
                        }
                    }
                });
                this.collection.data.select = list.join(',');
                this.collection.reset();
                this.fetchCollectionPart(() => {
                    this.groups = [];
                    this.groups = this.getGroupsFromCollection();

                    let valueKey = this.groups.map(group => group.key);

                    this.getCollectionFactory().create('AttributeGroup', collection => {
                        this.attributeGroupCollection = collection;
                        collection.select = 'sortOrder';
                        collection.maxSize = 200;
                        collection.offset = 0;
                        collection.whereAdditional = [
                            {
                                attribute: 'id',
                                type: 'in',
                                value: valueKey
                            }
                        ];

                        collection.fetch().then(() => {
                            let orderArray = [];
                            let noGroup;
                            this.groups.forEach(item => {
                                if (item.key === 'no_group') {
                                    item.sortOrder = 0;
                                    noGroup = item;
                                } else {
                                    this.attributeGroupCollection.forEach(model => {
                                        if (model.id === item.key) {
                                            item.sortOrder = model.get('sortOrder');
                                        }
                                    });
                                }
                                orderArray.push(item.sortOrder);
                            });
                            if (noGroup) {
                                noGroup.sortOrder = Math.max(...orderArray) + 1;
                            }
                            this.groups.sort(function(a, b) {
                                return a.sortOrder - b.sortOrder ;
                            });

                            if (callback) {
                                callback();
                            }
                        });
                    });
                });
            });
        },

        fetchCollectionPart(callback) {
            this.collection.fetch({remove: false, more: true}).then((response) => {
                if (this.collection.total > this.collection.length) {
                    this.fetchCollectionPart(callback);
                } else if (callback) {
                    callback();
                }
            });
        },

        getGroupsFromCollection() {
            let groups = [];

            (this.collection.models || []).forEach(model => {
                let key = model.get(this.groupKey);
                if (key === null) {
                    key = this.noGroup.key;
                }
                let label = model.get(this.groupLabel);
                if (label === null) {
                    label = this.translate(this.noGroup.label, 'labels', 'Global');
                }
                let group = groups.find(item => item.key === key);
                if (group) {
                    group.rowList.push(model.id);
                } else {
                    groups.push({
                        key: key,
                        id: key !== this.noGroup.key ? key : null,
                        label: label,
                        rowList: [model.id]
                    });
                }
            });

            return groups;
        },

        buildGroups() {
            this.groups.forEach(group => {
                this.getCollectionFactory().create(this.scope, collection => {
                    group.rowList.forEach(id => {
                        collection.add(this.collection.get(id));
                    });

                    collection.whereAdditional = [
                        {
                            type: 'linkedWith',
                            attribute: 'productFamilies',
                            value: [this.model.id]
                        },
                        {
                            type: group.key === 'no_group' ? 'isNull' : 'equals',
                            attribute: 'attributeGroupId',
                            value: group.key === 'no_group' ? null : group.key
                        },
                    ];

                    let viewName = this.defs.recordListView || this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'Record.List';

                    this.createView(group.key, viewName, {
                        collection: collection,
                        layoutName: this.layoutName,
                        listLayout: this.listLayout,
                        checkboxes: false,
                        rowActionsView: this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView),
                        buttonsDisabled: true,
                        el: `${this.options.el} .group[data-name="${group.key}"] .list-container`,
                        showMore: false
                    }, view => {
                        const setFieldsEditMode = () => {
                            (view.rowList || []).forEach(id => {
                                const rowView = view.getView(id);
                                if (rowView) {
                                    ['isRequired', 'isMultiChannel'].forEach(field => {
                                        const fieldView = rowView.getView(`${field}Field`);
                                        if (fieldView) {
                                            fieldView.setMode('edit');
                                            fieldView.reRender();
                                        }
                                    });
                                }
                            });
                        };
                        this.listenTo(view, 'after:render', setFieldsEditMode);
                        view.render();
                    });
                });
            });
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        setupActions() {
           Dep.prototype.setupActions.call(this);

           if (this.getAcl().check(this.model, 'edit')) {
               this.actionList.push({
                   label: 'Select Attribute Group',
                   action: 'selectAttributeGroup'
               });
           }
        },

        actionSelectAttributeGroup() {
            const scope= 'AttributeGroup';
            const viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', 'select']) || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                massRelateEnabled: false,
                whereAdditional: [
                    {
                        type: 'isLinked',
                        attribute: 'attributes'
                    }
                ]
            }, dialog => {
                dialog.render();
                this.notify(false);
                dialog.once('select', selectObj => {
                    if (!Array.isArray(selectObj)) {
                        return;
                    }
                    const boolFilterList = this.getSelectBoolFilterList() || [];
                    const data = {
                        massRelate: true,
                        where: [
                            {
                                type: 'bool',
                                value: boolFilterList,
                                data: this.getSelectBoolFilterData(boolFilterList)
                            },
                            {
                                attribute: 'attributeGroupId',
                                type: 'in',
                                value: selectObj.map(model => model.id)
                            }
                        ]
                    };

                    this.notify('Saving...', 'success');
                    this.ajaxPostRequest(`${this.model.name}/${this.model.id}/${this.link}`, data).then(response => {
                        if (response) {
                            this.notify('Linked', 'success');
                            this.actionRefresh();
                        } else {
                            this.notify('Error occurred', 'error');
                        }
                    });
                });
            });
        },

        actionRefresh() {
            this.fetchCollectionGroups(() => this.reRender());
        },

        unlinkAttributeGroup(data) {
            let id = data.id;
            if (!id) {
                return;
            }

            let group = this.groups.find(group => group.id === id);
            if (!group || !group.rowList) {
                return;
            }

            this.confirm({
                message: this.translate('unlinkAttributeGroupConfirmation', 'messages', this.model.name),
                confirmText: this.translate('Unlink')
            }, function () {
                this.notify('Unlinking...');
                $.ajax({
                    url: `${this.model.name}/${this.link}/relation`,
                    data: JSON.stringify({
                        ids: [this.model.id],
                        foreignIds: group.rowList
                    }),
                    type: 'DELETE',
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.model.trigger('after:unrelate');
                        this.actionRefresh();
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        actionUnlinkRelated: function (data) {
            var id = data.id;

            this.ajaxGetRequest(`ProductFamily/${this.model.id}/productsCount`, {attributeId: id}).then(response => {
                Espo.TreoUi.confirmWithBody('', {
                    message: this.translate('unlinkRecordConfirmation', 'messages'),
                    confirmText: this.translate('Unlink'),
                    cancelText: this.translate('Cancel'),
                    body: this.getUnlinkHtml(response)
                }, function () {
                    var model = this.collection.get(id);
                    this.notify('Unlinking...');
                    $.ajax({
                        url: this.collection.url,
                        type: 'DELETE',
                        data: JSON.stringify({
                            id: id
                        }),
                        contentType: 'application/json',
                        success: function () {
                            this.notify('Unlinked', 'success');
                            this.collection.fetch();
                            this.model.trigger('after:unrelate');
                        }.bind(this),
                        error: function () {
                            this.notify('Error occurred', 'error');
                        }.bind(this),
                    });
                }, this);
            });
        },

        getUnlinkHtml(count) {
            return `
                <div class="row">
                    <div class="col-xs-12">
                        <span class="confirm-message">${this.translate('removeRecordConfirmation', 'messages')}</span>
                    </div>
                    <div class="col-xs-12">
                        <div style="margin-top: 15px;">
                            <span class="product-counts-message">${this.translate('productsCountWithAttribute', 'messages', 'Attribute').replace('{count}', count)}</span>
                        </div>
                    </div>
                </div>`;
        }

    })
);