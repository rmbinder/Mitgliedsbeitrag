<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
  
    {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']}

    <div class="card admidio-field-group">
        <div class="card-header">{$groupHeader}</div>
        <div class="card-body">
           
            <div class="table-responsive">
                <table class="table table-condensed" id="viewdefinitions_fields_table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">{$l10n->get('SYS_ABR_NO')}</th>
                            <th style="width: 80%;">{$l10n->get('SYS_CONTENT')}</th>
                            </th>
                            <th style="width: 6%;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="viewdefinitions_fields_tbody">
                    </tbody>
                </table>
            </div>
            <div class="btn-group" role="group">
                {include 'sys-template-parts/form.button.tpl' data=$elements['btn_add_column']}
                {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']}
            </div>
        </div>
    </div>
 
    <div class="form-alert" style="display: none;">&nbsp;</div>
  
</form>
