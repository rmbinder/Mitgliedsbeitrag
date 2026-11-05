<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {$l10n->get('PLG_MEMBERSHIPFEE_ADVANCED_ROLE_EDITING_DESC')}
    
    <div style="width:100%; height:700px; overflow:auto; border:20px;">       
        {foreach $alt_fix_rollen as $key => $data}
            <div class="card admidio-field-group">
            <div class="card-header">{$data.rolle}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.input.tpl' data=$elements["rol_cost$key"]}  
                    {include 'sys-template-parts/form.select.tpl' data=$elements["rol_cost_period$key"]}  
                    {include 'sys-template-parts/form.input.tpl' data=$elements["rol_description$key"]}    
                </div>
            </div>
        {/foreach}
    </div>
          
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
