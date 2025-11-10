<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <p>{$l10n->get('PLG_MITGLIEDSBEITRAG_UNINST_SECURITY_CHECK')}</p>
               
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_exit']} 
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_continue']}
     
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
