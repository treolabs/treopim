<select name="{{name}}" class="form-control main-element attribute-value">
    {{options params.options value scope=scope field=name translatedOptions=translatedOptions}}
</select>
<button type="button" class="btn btn-default attribute-value-plus" data-name="{{name}}" data-action="addAttributeValueOption"><span class="fas fa-plus"></span></button>
<style>
    select.attribute-value {
        width: calc(100% - 34px);
    }
    button.attribute-value-plus {
        position: absolute;
        top: 0;
        right: 0;
        width: 35px;
    }
</style>