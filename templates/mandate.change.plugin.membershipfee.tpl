<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['mandateid']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['mandat_schieben']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['origmandateid']}
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['iban']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['iban_schieben']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['origiban']}
    
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['bankchanged']}
         
    {include 'sys-template-parts/form.input.tpl' data=$elements['bic']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['bank']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['origdebtoragent']}
               
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['warning']}  
                              
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save_configurations']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
