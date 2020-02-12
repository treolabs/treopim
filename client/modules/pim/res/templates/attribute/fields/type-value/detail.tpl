<div class="main-field{{#if hideMainOption}} hidden{{/if}}">{{#unless isEmpty}}{{{value}}}{{else}}{{translate 'None'}}{{/unless}}</div>
{{#each langValues}}
<div class="multilang-labels">
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{translate ../name category='fields' scope=../scope}} &rsaquo; {{shortLang}}</span>
    </label>
    <div>{{#if value}}{{{value}}}{{else}}{{translate 'None'}}{{/if}}</div>
</div>
{{/each}}