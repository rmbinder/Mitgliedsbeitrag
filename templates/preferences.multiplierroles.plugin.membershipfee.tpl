<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {if (isset($elements['multiplier_roles']))}
        {include 'sys-template-parts/form.select.tpl' data=$elements['multiplier_roles']}
    {else} 
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['alert_no_roles']}
    {/if}
        
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['desc']}
  
    {if (isset($elements['multiplier_roles']))}
        {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    {/if} 
      
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}
