<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.description.tpl' data=$elements['role_selection_defaults_desc']}
      
    {include 'sys-template-parts/form.select.tpl' data=$elements['recalculation_roleselection_default']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['payments_roleselection_default']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['duedates_roleselection_default']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['membernumber_roleselection_default']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['create_mandate_id_roleselection_default']}
          
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_role_selection_defaults']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}