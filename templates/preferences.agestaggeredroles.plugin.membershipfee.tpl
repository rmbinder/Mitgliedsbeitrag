<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
                                
    {include 'sys-template-parts/form.input.tpl' data=$elements['altersrollen_offset']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['altersrollen_offset_info']}
    <hr>
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['delimiter']}  
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['delimiter_info']} 
                 
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>
                    
    {for $conf =0 to $num_agestaggeredroles-1}
        {include 'sys-template-parts/form.input.tpl' data=$elements["altersrollen_token$conf"]}  

        {if (isset($elements["add_trash$conf"]))}
            {include 'sys-template-parts/form.custom-content.tpl' data=$elements["add_trash$conf"]}  
        {/if}
    {/for}
                
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['add_config']}   
                              
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
