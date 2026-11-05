<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {$l10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_DELETE_DESC')}
    <br><br>          
    {include 'sys-template-parts/form.radio.tpl' data=$elements['delete']} 
                              
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_delete']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
