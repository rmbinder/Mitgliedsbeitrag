<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.select.tpl' data=$elements['access_preferences']}
 
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_access']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}
