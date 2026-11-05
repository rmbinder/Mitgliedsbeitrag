<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>
   
    <div style="width:100%; height:700px; overflow:auto; border:20px;">                
        {for $conf =0 to $num_familyroles-1}
            <div class="card admidio-field-group">
                <div class="card-header">{$conf+1}. {$l10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLE')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.input.tpl' data=$elements["familienrollen_prefix$conf"]}  
                    {include 'sys-template-parts/form.input.tpl' data=$elements["familienrollen_beitrag$conf"]}    
                    {include 'sys-template-parts/form.select.tpl' data=$elements["familienrollen_zeitraum$conf"]}    
                    {include 'sys-template-parts/form.input.tpl' data=$elements["familienrollen_beschreibung$conf"]}    

                    {if (isset($elements["add_trash$conf"]))}
                        {include 'sys-template-parts/form.custom-content.tpl' data=$elements["add_trash$conf"]}  
                    {/if}               
                </div>
            </div>
        {/for}
    </div>
    
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['add_config']}   
                              
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_familyroles']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
