<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.input.tpl' data=$elements['dateiname']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['direct_debit_format']}
    <hr>
    {include 'sys-template-parts/form.input.tpl' data=$elements['kontroll_dateiname']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['kontroll_dateityp']}
    <hr>
    {include 'sys-template-parts/form.input.tpl' data=$elements['vorabinformation_dateiname']}
    {include 'sys-template-parts/form.select.tpl' data=$elements['vorabinformation_dateityp']}
    <hr> 
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['duedate_for_all']}
          
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
