<input type="text" class="main-element form-control attribute-value-field" name="{{name}}" value="{{value}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} autocomplete="off" placeholder="{{translate 'typeAndPressEnter' category='messages' scope='Global'}}">
<button type="button" class="btn btn-default remove-field-button" data-name="{{name}}" data-action="removeField"><span class="fas fa-times"></span></button>
<style>
    input.attribute-value-field {
        width: calc(100% - 34px);
    }
    button.remove-field-button {
        position: absolute;
        top: 0;
        right: 0;
        width: 35px;
    }
</style>