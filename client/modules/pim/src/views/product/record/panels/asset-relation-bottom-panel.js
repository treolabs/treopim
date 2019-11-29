Espo.define('pim:views/product/record/panels/asset-relation-bottom-panel', 'dam:views/asset_relation/record/panels/bottom-panel',
    Dep => Dep.extend({
        _createTypeBlock(model, show, callback) {
            model.set({
                entityName: this.defs.entityName,
                entityId  : this.model.id,
                entityModel  : this.model
            });
            this.createView(model.get('name'), "pim:views/product/record/panels/asset-type-block", {
                model: model,
                el   : this.options.el + ' .group[data-name="' + model.get("name") + '"]',
                sort : this.sort,
                show : show
            });
        }
    })
);