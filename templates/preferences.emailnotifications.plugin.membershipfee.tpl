<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['emailnotifications']} 
 
    {include 'sys-template-parts/form.multiline.tpl' data=$elements['mail_text']} 
    {include 'sys-template-parts/form.multiline.tpl' data=$elements['pre_notification_text']} 
    {include 'sys-template-parts/form.multiline.tpl' data=$elements['bill_text']}  
            
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
