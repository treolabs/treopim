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

Espo.define('pim:views/product/fields/image', 'views/fields/image',
    Dep => Dep.extend({

        urlImage: null,

        imageId: null,

        imageName: null,

        sizeImage:{
            'x-small': [64, 64],
            'small': [128, 128],
            'medium': [256, 256],
            'large': [512, 512]
        },

        setup() {
            Dep.prototype.setup.call(this);
            this.getMainImage();

            this.events['click a[data-action="showRemoteImagePreview"]'] = (e) => {
                e.preventDefault();

                var url = this.urlImage;
                this.createView('preview', 'pim:views/modals/remote-image-preview', {
                    url: url,
                    model: this.model,
                    name: this.model.get(this.nameName)
                }, function (view) {
                    view.render();
                });
            };

            this.listenTo(this.model, 'updateProductImage', () => {
                this.getMainImage();
            });

        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'list') {
                this.$el.find('img').css({'max-height': '120px', 'max-width': '100%'});
            }
        },

        getMainImage() {
            if (this.model.id) {
                this.ajaxGetRequest(`Product/${this.model.id}/productImages`, {
                    maxSize: 1,
                    offset: 0,
                    sortBy: 'sortOrder'
                })
                    .then(data => {
                        if (data.list.length) {
                            this.urlImage = data.list[0].imageLink;
                            this.imageId = data.list[0].imageId;
                            this.imageName = data.list[0].imageName;
                        } else {
                            this.urlImage = null;
                            this.imageId = null;
                            this.imageName = null;
                        }
                        this.model.trigger('main-image-updated');
                        this.reRender();
                    })
            }
        },
        getValueForDisplay() {
            let imageSize = [];

            if (this.sizeImage.hasOwnProperty(this.previewSize)) {
                imageSize = this.sizeImage[this.previewSize]
            } else {
                imageSize = this.sizeImage['small']
            }

            if (!this.imageId && this.urlImage && this.showPreview) {
                return `<div class="attachment-preview"><a data-action="showRemoteImagePreview" href="${this.urlImage}"><img src="${this.urlImage}" style="max-width:${imageSize[0]}px; max-height:${imageSize[1]}px;"></a></div>`;
            } else {
                this.model.set({
                    [this.idName]: this.imageId,
                    [this.nameName]: this.imageName
                });
                return Dep.prototype.getValueForDisplay.call(this);
            }
        }

    })
);
