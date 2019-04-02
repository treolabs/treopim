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

Espo.define('pim:views/product/record/catalog-tree-panel/category-tree', 'view',
    Dep => Dep.extend({

        template: 'pim:product/record/catalog-tree-panel/category-tree',

        events: {
            'show.bs.collapse div.panel-collapse.collapse[class^="catalog-"]': function (e) {
                e.stopPropagation();
                $(e.currentTarget).prev('button.catalog-link').find('span.caret').addClass('caret-up');
                this.$el.parent().find(`.panel-collapse.collapse[class^="catalog-"].in`).collapse('hide');
            },
            'hide.bs.collapse div.panel-collapse.collapse[class^="catalog-"]': function (e) {
                e.stopPropagation();
                $(e.currentTarget).prev('button.catalog-link').find('span.caret').removeClass('caret-up');
            },
            'show.bs.collapse div.panel-collapse.collapse[class^="category-"]': function (e) {
                e.stopPropagation();
                this.unfold($(e.currentTarget).data('id'));
            },
            'hide.bs.collapse div.panel-collapse.collapse[class^="category-"]': function (e) {
                e.stopPropagation();
                this.fold($(e.currentTarget).data('id'));
            },
            'click button.category.child-category': function (e) {
                let button = $(e.currentTarget);
                let categoryId = button.data('id');
                let catalogPanel = button.parents('div.panel-collapse.collapse[class^="catalog-"]');
                let catalogId = catalogPanel.data('id');
                this.trigger('category-tree-select', categoryId, catalogId);
            }
        },
        
        data() {
            return {
                scope: this.scope,
                catalog: this.options.catalog,
                rowList: this.getRowList(),
                hash: this.getRandomHash()
            }
        },

        setup() {
            this.scope = this.options.scope || this.scope;
        },

        getRowList() {
            let arr = [];
            let rootCategory = this.options.catalog.categoryTree || {};
            arr.push({
                id: rootCategory.id,
                html: (rootCategory.children && rootCategory.children.length) ? this.getParentHtml(rootCategory) : this.getChildHtml(rootCategory)
            });
            return arr;
        },

        getParentHtml(root) {
            let hash = this.getRandomHash();
            let children = '';

            if (root.children && root.children.length) {
                root.children.sort((a, b) => {
                    return a.name.localeCompare(b.name);
                });
                root.children.sort((a, b) => {
                    if ((a.children && a.children.length) && !b.children) {
                        return -1;
                    }
                    if ((b.children && b.children.length) && !a.children) {
                        return 1;
                    }
                    return 0;
                });
            }

            (root.children || []).forEach(child => {
                if (child.children && child.children.length) {
                    children += this.getParentHtml(child);
                } else {
                    children += this.getChildHtml(child);
                }
            });

            return `` +
                `<li data-id="${root.id}" class="list-group-item child">` +
                    `<button class="btn btn-link category category-icons" data-toggle="collapse" data-target=".category-${hash}" data-id="${root.id}">` +
                        `<span class="fas fa-chevron-right"></span>` +
                        `<span class="fas fa-chevron-down hidden"></span>` +
                    `</button>` +
                    `<button class="btn btn-link category child-category" data-id="${root.id}">` +
                        `${root.name}` +
                    `</button>` +
                    `<div class="category-${hash} panel-collapse collapse" data-id="${root.id}">` +
                        `<ul class="list-group list-group-tree">` +
                            `${children}`+
                        `</ul>` +
                    `</div>` +
                `</li>`;
        },

        getChildHtml(child) {
            return `` +
                `<li data-id="${child.id}" class="list-group-item child">` +
                    `<button class="btn btn-link category child-category" data-id="${child.id}" href="javascript">` +
                        `${child.name}` +
                    `</button>` +
                `</li>`;
        },

        getRandomHash() {
            return Math.floor((1 + Math.random()) * 0x100000000)
                .toString(16)
                .substring(1);
        },
        
        fold(id) {
            let button = this.$el.find(`button.category-icons[data-id="${id}"]`);
            button.find('span.fa-chevron-right').removeClass('hidden');
            button.find('span.fa-chevron-down').addClass('hidden');
        },

        unfold(id) {
            let button = this.$el.find(`button.category-icons[data-id="${id}"]`);
            button.find('span.fa-chevron-right').addClass('hidden');
            button.find('span.fa-chevron-down').removeClass('hidden');
        },

    })
);