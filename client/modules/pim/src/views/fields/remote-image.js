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

Espo.define('pim:views/fields/remote-image', 'pim:views/fields/full-width-list-image',
    Dep => Dep.extend({

        urlField: null,

        sizeImage:{
            'x-small': [64, 64],
            'small': [128, 128],
            'medium': [256, 256],
            'large': [512, 512]
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.events['click a[data-action="showRemoteImagePreview"]'] = (e) => {
                e.preventDefault();

                var url = this.model.get(this.urlField);
                this.createView('preview', 'pim:views/modals/remote-image-preview', {
                    url: url,
                    model: this.model,
                    name: this.model.get(this.nameName)
                }, function (view) {
                    view.render();
                });
            };
        },

        getValueForDisplay() {
            let imageSize = [];
            let id = this.model.get(this.idName);
            let url = this.model.get(this.urlField);
            if (this.sizeImage.hasOwnProperty(this.previewSize)) {
                imageSize = this.sizeImage[this.previewSize]
            } else {
                imageSize = this.sizeImage['small']
            }

            if (!id && url && this.showPreview) {
                return `<div class="attachment-preview"><a data-action="showRemoteImagePreview" href="${url}"><img src="${url}" style="max-width:${imageSize[0]}px; max-height:${imageSize[1]}px;"></a></div>`;
            } else {
                return Dep.prototype.getValueForDisplay.call(this);
            }
        }

    })
);