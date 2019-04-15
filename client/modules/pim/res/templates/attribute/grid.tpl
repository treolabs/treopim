{{#if gridLayout}}
{{#each gridLayout}}
<div data-group="{{id}}">
    <div class="row"><div class="product-attribute col-sm-12"><b>{{label}}</b></div></div>
    {{#each rows}}
    <div class="row">
        {{#each this}}
        <div class="cell {{#if oneInRow}}col-sm-12{{else}}col-sm-6{{/if}} form-group attribute-cell" data-name="{{name}}">
            {{#if deletable}}
            <a href="javascript:" class="pull-right inline-remove-link hidden" data-name="{{../name}}"><span class="fas fa-times"></span></a>
            {{/if}}
            {{#if editable}}
            <a href="javascript:" class="pull-right inline-edit-link edit-attribute hidden" data-name="{{name}}"><span class="fas fa-pencil-alt fa-sm"></span></a>
            <a href="javascript:" class="pull-right inline-cancel-link hidden">{{translate 'Cancel'}}</a>
            <a href="javascript:" class="pull-right inline-save-link hidden">{{translate 'Update'}}</a>
            <label class="control-label" data-name="{{name}}">
                <a href="javascript:" class="action show-attribute-value-modal" data-action="showAttributeValueModal" data-name="{{name}}"><span class="label-text">{{label}}</span></a>
            </label>
            {{else}}
            <label class="control-label" data-name="{{name}}">
                <span class="label-text">{{label}}</span>
            </label>
            {{/if}}
            <div class="field" data-name="{{name}}"></div>
        </div>
        {{/each}}
    </div>
    {{/each}}
</div>
{{/each}}
{{else}}
{{translate 'No Data'}}
{{/if}}