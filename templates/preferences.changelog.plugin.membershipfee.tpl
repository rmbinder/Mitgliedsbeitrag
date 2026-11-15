<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['suspend_history']}
                      
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_changelog']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
