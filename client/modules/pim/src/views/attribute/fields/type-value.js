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

Espo.define('pim:views/attribute/fields/type-value', 'multilang:views/fields/array-multilang',
    Dep => Dep.extend({

        _timeouts: {},

        selectedComplex: null,

        disableMultiLang: false,

        resetValueTypes: ['array', 'arrayMultiLang'],

        multiLangFieldTypes: ['enumMultiLang', 'multiEnumMultiLang'],

        editTemplate: 'pim:attribute/fields/type-value/edit',

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

        setup() {
            Dep.prototype.setup.call(this);

            this.populateSelectedComplexFromModel();
            this.listenTo(this.model, this.langFieldNameList.reduce((prev, curr) => `${prev} change:${curr}`, `change:${this.name}`), () => {
                this.populateSelectedComplexFromModel();
            });

            this.setDisableMultiLang();

            this.listenTo(this.model, 'change:type', () => {
                if (this.resetValueTypes.includes(this.model.get('type'))) {
                    this.resetValue();
                }
                this.setDisableMultiLang();
                this.reRender();
            });

            this.off('customInvalid');
            this.on('customInvalid', function (name, index) {
                let input = this.getCellElement().find(`.form-control[data-name="${name}"][data-index="${index}"]`);
                let label = this.getCellElement().find('.control-label[data-name="'+ this.name + '"]');
                label.addClass('multilang-error-label');
                input.addClass('multilang-error-form-control');
                this.$el.one('click', function () {
                    label.removeClass('multilang-error-label');
                    input.removeClass('multilang-error-form-control');
                });
                this.once('render', function () {
                    label.removeClass('multilang-error-label');
                    input.removeClass('multilang-error-form-control');
                });
            }, this);
        },

        populateSelectedComplexFromModel() {
            this.selectedComplex = {
                [this.name]: Espo.Utils.cloneDeep(this.model.get(this.name)) || []
            };
            this.langFieldNameList.forEach(field => {
                this.selectedComplex[field] = Espo.Utils.cloneDeep(this.model.get(field)) || []
            });
        },

        data() {
            let data = Dep.prototype.data.call(this);
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
                        value: (this.selectedComplex[name] || [])[index],
                        shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    });
                });
                return group;
            });
            return data;
        },

        setDisableMultiLang() {
            this.disableMultiLang = !this.multiLangFieldTypes.includes(this.model.get('type'));
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

        fetchFromDom() {
            let data = {};
            data[this.name] = [];
            this.langFieldNameList.forEach(item => data[item] = []);
            this.$el.find('.option-group').each((index, element) => {
                $(element).find('.option-item input').each((i, el) => {
                    let $el = $(el);
                    let name = $el.data('name').toString();
                    let value = $el.val().toString();
                    data[name][index] = value;
                });
            });
            return data;
        },

        validateRequired() {
            let error = false;
            if (this.isRequired()) {
                let value = this.model.get(this.name);
                if (!value || !value.length) {
                    error = true;
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg, '.add-attribute-type-value');
                    this.trigger('customInvalid', this.name);
                } else {
                    value.forEach((item, index) => {
                        if (!item.toString().length) {
                            error = true;
                            let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                            this.showValidationMessage(msg, `input[data-name="${this.name}"][data-index="${index}"]`);
                            this.trigger('customInvalid', this.name, index);
                        }
                    });
                    this.langFieldNameList.forEach(name => {
                        (this.model.get(name) || []).forEach((item, index) => {
                            if (!item.toString().length) {
                                error = true;
                                let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name)
                                    + " › " + name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase());
                                this.showValidationMessage(msg, `input[data-name="${name}"][data-index="${index}"]`);
                                this.trigger('customInvalid', name, index);
                            }
                        });
                    });
                }
            }
            return error;
        },

        validateNumberOfOptions() {
            return false;
        },

        showValidationMessage: function (message, target) {
            var $el;

            target = target || '.array-control-container';

            if (typeof target === 'string' || target instanceof String) {
                $el = this.$el.find(target);
            } else {
                $el = $(target);
            }

            if (!$el.size() && this.$element) {
                $el = this.$element;
            }
            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual'
            }).popover('show');

            var isDestroyed = false;

            $el.closest('.field').one('mousedown click', function () {
                if (isDestroyed) return;
                $el.popover('destroy');
                isDestroyed = true;
            });

            this.once('render remove', function () {
                if (isDestroyed) return;
                if ($el) {
                    $el.popover('destroy');
                    isDestroyed = true;
                }
            });

            if (this._timeouts[target]) {
                clearTimeout(this._timeouts[target]);
            }

            this._timeouts[target] = setTimeout(function () {
                if (isDestroyed) return;
                $el.popover('destroy');
                isDestroyed = true;
            }, 3000);
        },

    })
);

