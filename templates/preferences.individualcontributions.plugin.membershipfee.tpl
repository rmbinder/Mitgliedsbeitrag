<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {$l10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_DESC')}
    <br><br> 
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['individual_contributions_module_enabled']}

    {for $conf =0 to $num_individualcontributions-1}
        <strong>{include 'sys-template-parts/form.description.tpl' data=$elements["header$conf"]}</strong>
        {include 'sys-template-parts/form.input.tpl' data=$elements["individual_contributions_desc$conf"]}  
        {include 'sys-template-parts/form.input.tpl' data=$elements["individual_contributions_short_desc$conf"]}  
        {include 'sys-template-parts/form.select.tpl' data=$elements["individual_contributions_role$conf"]}  
        {include 'sys-template-parts/form.input.tpl' data=$elements["individual_contributions_amount$conf"]}  
        {include 'sys-template-parts/form.select.tpl' data=$elements["individual_contributions_profilefield$conf"]}  
                                      
        {if (isset($elements["add_trash$conf"]))}
            {include 'sys-template-parts/form.custom-content.tpl' data=$elements["add_trash$conf"]}  
        {/if}  
        {include 'sys-template-parts/form.description.tpl' data=$elements['line']} 
    {/for}

    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['add_config']}   
          
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_individualcontributions']} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
