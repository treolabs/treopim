<div class="main-field{{#if hideMainOption}} hidden{{/if}}">{{#unless isEmpty}}{{{value}}}{{else}}{{translate 'None'}}{{/unless}}</div>
{{#each optionGroups}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{translate ../../name category='fields' scope=../../scope}} &rsaquo; {{shortLang}}</span>
    </label>
    <div>{{#unless isEmpty}}{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}{{else}}{{translate 'None'}}{{/unless}}</div>
{{/each}}