<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['prefix_fam']}  
    {include 'sys-template-parts/form.input.tpl' data=$elements['prefix_mem']}  
    {include 'sys-template-parts/form.input.tpl' data=$elements['prefix_pay']}  
    {include 'sys-template-parts/form.input.tpl' data=$elements['min_length']}  
    {include 'sys-template-parts/form.select.tpl' data=$elements['data_field']}    
    {include 'sys-template-parts/form.description.tpl' data=$elements['mandate_management_desc']}    
                      
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_mandatemanagement']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
