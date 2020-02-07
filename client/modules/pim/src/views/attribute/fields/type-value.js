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

Espo.define('pim:views/attribute/fields/type-value', 'views/fields/array',
    Dep => Dep.extend({

        editTemplate: 'pim:attribute/fields/type-value/edit',

        disableMultiLang: false,

        multiLangFieldTypes: ['enum', 'multiEnum'],

        resetValueTypes: ['array'],

        selectedComplex: null,

        events: _.extend({
            'click [data-action="addNewValue"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.addNewValue();
            },
            'click [data-action="removeGroup"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                let index = $(e.currentTarget).data('index');
                this.removeGroup(index);
            },
            'change input[data-name][data-index]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.trigger('change');
            }
        }, Dep.prototype.events),

        data() {
            let data = Espo.Utils.cloneDeep(Dep.prototype.data.call(this));

            data.scope = this.model.name;
            data.disableMultiLang = this.disableMultiLang;
            data.name = this.name;

            data.optionGroups = (this.selectedComplex[this.name] || []).map((item, index) => {
                let group = {
                    options: [
                        {
                            name: this.name,
                            value: item,
                            shortLang: ''
                        }
                    ]
                };
                this.langFieldNameList.forEach(name => {
                    group.options.push({
                        name: name,
                        value: (this.selectedComplex[name] || [])[index] || (this.selectedComplex[this.name] || [])[index],
                        shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    });
                });
                return group;
            });

            this.modifyDataByType(data);

            return data;
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.langFieldNameList = this.getLangFieldNameList();

            this.populateSelectedComplexFromModel();
            this.listenTo(this.model, this.langFieldNameList.reduce((prev, curr) => `${prev} change:${curr}`, `change:${this.name}`), () => {
                this.populateSelectedComplexFromModel();
            });

            this.setDisableMultiLang();

            this.listenTo(this.model, 'change:isMultilang change:type', () => {
                if (this.resetValueTypes.includes(this.model.get('type'))) {
                    this.resetValue();
                }
                this.setDisableMultiLang();
                this.setMode(this.mode);
                this.reRender();
            });
        },

        getLangFieldNameList() {
            let result = [];

            const inputLanguageList = this.getConfig().get('inputLanguageList') || [];
            if (this.getConfig().get('isMultilangActive') && inputLanguageList.length) {
                result = inputLanguageList.map(lang => {
                    return lang.split('_').reduce((prev, curr) => prev + Espo.Utils.upperCaseFirst(curr.toLowerCase()), this.name);
                });
            }

            return result;
        },

        populateSelectedComplexFromModel() {
            this.selectedComplex = {
                [this.name]: Espo.Utils.cloneDeep(this.model.get(this.name)) || []
            };
            this.langFieldNameList.forEach(field => {
                this.selectedComplex[field] = Espo.Utils.cloneDeep(this.model.get(field)) || []
            });
        },

        setMode: function (mode) {
            // prepare mode
            this.mode = mode;

            let property = mode + 'Template';

            // prepare type
            let type = (this.model.get('type') === 'unit') ? 'enum' : 'array';

            // set template
            this.template = this[property] || 'fields/' + Espo.Utils.camelCaseToHyphen(type) + '/' + this.mode;
        },

        modifyDataByType(data) {
            if (this.model.get('type') === 'unit') {
                const options = [];
                const translatedOptions = {};

                $.each(this.getConfig().get('unitsOfMeasure') || {}, (key, value) => {
                    options.push(key);
                    translatedOptions[key] = this.getLanguage().get('Global', 'measure', key);
                });

                data.params.options = options;
                data.translatedOptions = translatedOptions;

                let value = this.model.get(this.name);
                if (
                    value !== null
                    &&
                    value !== ''
                    ||
                    value === '' && (value in (translatedOptions || {}) && (translatedOptions || {})[value] !== '')
                ) {
                    data.isNotEmpty = true;
                }
            }
        },

        setDisableMultiLang() {
            this.disableMultiLang = !this.model.get('isMultilang') && !this.model.get('locale')
                || !this.multiLangFieldTypes.includes(this.model.get('type'));
            this.langFieldNameList = this.disableMultiLang ? [] : this.getLangFieldNameList();
        },

        resetValue() {
            this.selectedComplex = {
                [this.name]: null
            };
            this.langFieldNameList.forEach(lang => this.selectedComplex[lang] = null);
            this.model.set(this.selectedComplex);
        },

        addNewValue() {
            let data = {
                [this.name]: (this.selectedComplex[this.name] || []).concat([''])
            };
            this.langFieldNameList.forEach(field => {
                data[field] = (this.selectedComplex[field] || []).concat([''])
            });
            this.selectedComplex = data;
            this.reRender();
            this.trigger('change');
        },

        removeGroup(index) {
            let value = this.selectedComplex[this.name] || [];
            value.splice(index, 1);
            let data = {
                [this.name]: value
            };
            this.langFieldNameList.forEach(field => {
                let value = this.selectedComplex[field] || [];
                value.splice(index, 1);
                data[field] = value;
            });
            this.selectedComplex = data;
            this.reRender();
            this.trigger('change');
        },

        fetch() {
            return this.fetchFromDom();
        },

        fetchFromDom() {
            const data = {};

            data[this.name] = [];
            this.langFieldNameList.forEach(item => data[item] = []);

            this.$el.find('.option-group').each((index, element) => {
                $(element).find('.option-item input').each((i, el) => {
                    const $el = $(el);
                    const name = $el.data('name').toString();
                    data[name][index] = $el.val().toString();
                });
            });

            this.modifyFetchByType(data);

            return data;
        },

        modifyFetchByType(data) {
            if (this.model.get('type') === 'unit') {
                data = {};
                data[this.name] = [this.$el.find(`[name="${this.name}"]`).val()];
            }
        },

    })
);