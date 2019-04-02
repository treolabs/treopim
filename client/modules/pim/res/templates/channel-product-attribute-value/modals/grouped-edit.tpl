<div class="edit-container record">
    <div class="edit">
        <div class="row">
            <div class="{{#if sideFieldList}}col-md-7{{else}}col-md-12{{/if}}">
                <div class="middle">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <div class="cell col-sm-12 form-group" data-name="attributeValue">
                                    <label class="control-label" data-name="attributeValue">
                                        <span class="label-text">{{translate attributeName}}</span>
                                    </label>
                                    <div class="field" data-name="attributeValue"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{#if sideFieldList}}
            <div class="side col-md-5">
                <div class="panel panel-default panel-default" data-name="default">
                    <div class="panel-body panel-body-form" data-name="default">
                        <div class="row">
                            {{#each sideFieldList}}
                            <div class="cell form-group col-sm-6 col-md-12" data-name="{{this}}">
                                <label class="control-label" data-name="{{this}}">
                                    <span class="label-text">{{translate this scope=../scope category='fields'}}</span>
                                </label>
                                <div class="field" data-name="{{this}}"></div>
                            </div>
                            {{/each}}
                        </div>
                    </div>
                </div>
            </div>
            {{/if}}
        </div>
    </div>
</div>
