<div class="edit-container record">
    <div class="edit" id="product-attribute-value-{{name}}">
        <div class="row">
            <div class="col-md-7">
                <div class="middle">
                    <div class="panel panel-default">
                        <div class="panel-body panel-body-form">
                            <div class="row">
                                <div class="cell col-sm-12 form-group" data-name="{{name}}">
                                    <label class="control-label" data-name="{{name}}">
                                        <span class="label-text">{{translate 'value' category='fields' scope=scope}}</span>
                                    </label>
                                    <div class="field" data-name="{{name}}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="side col-md-5">
                <div class="panel panel-default panel-default" data-name="default">
                    <div class="panel-body panel-body-form" data-name="default">
                        <div class="row">
                            {{#each sideFieldList}}
                            <div class="cell form-group col-sm-12" data-name="{{this}}">
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
        </div>
    </div>
</div>