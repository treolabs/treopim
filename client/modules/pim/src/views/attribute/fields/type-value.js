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

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:type', () => {
                this.resetValue();
                this.setMode(this.mode);
                this.reRender();
            });
        },

        setMode: function (mode) {
            // prepare mode
            this.mode = mode;

            // prepare type
            let type = (this.model.get('type') === 'unit') ? 'enum' : 'array';

            // set template
            this.template = 'fields/' + Espo.Utils.camelCaseToHyphen(type) + '/' + this.mode;
        },

        data() {
            let data = Dep.prototype.data.call(this);

            data.name = this.name;
            data = this.modifyDataByType(data);

            return data;
        },

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            data = this.modifyFetchByType(data);

            return data;
        },

        modifyFetchByType(data) {
            let fetchedData = data;
            if (this.model.get('type') === 'unit') {
                fetchedData = {};
                fetchedData[this.name] = [this.$el.find(`[name="${this.name}"]`).val()];
            }

            return fetchedData;
        },

        modifyDataByType(data) {
            data = Espo.Utils.cloneDeep(data);
            if (this.model.get('type') === 'unit') {
                let options = Object.keys(this.getConfig().get('unitsOfMeasure') || {});
                data.params.options = options;
                let translatedOptions = {};
                options.forEach(item => translatedOptions[item] = this.getLanguage().get('Global', 'measure', item));
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

            return data;
        },

        resetValue() {
            this.selectedComplex = {[this.name]: null};
            this.model.set(this.selectedComplex);
        }

    })
);