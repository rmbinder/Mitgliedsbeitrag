<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <p>{$l10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA_INFO')}</p>
    {include 'sys-template-parts/form.input.tpl' data=$elements['iban']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['bic']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['bank']}    
    {include 'sys-template-parts/form.input.tpl' data=$elements['creditor']}
    
    {if (isset($elements['iconlink_creditorschieben']))}
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['iconlink_creditorschieben']}
    {/if}
    {if (isset($elements['origcreditor']))}
        {include 'sys-template-parts/form.input.tpl' data=$elements['origcreditor']}
    {/if}
    {include 'sys-template-parts/form.input.tpl' data=$elements['ci']}
     {if (isset($elements['iconlink_cischieben']))}
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['iconlink_cischieben']}
    {/if}
    {if (isset($elements['origci']))}
        {include 'sys-template-parts/form.input.tpl' data=$elements['origci']}
    {/if}
     {if (isset($elements['alert_mandate_change_info']))}
     {include 'sys-template-parts/form.custom-content.tpl' data=$elements['alert_mandate_change_info']}
         {/if}
    {if (isset($elements['mandate_change_desc_cust_cont']))}
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['mandate_change_desc_cust_cont']}
    {/if}
          
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_accountdata']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
