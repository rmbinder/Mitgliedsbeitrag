<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <div id="plugin-{$name}" class="admidio-plugin-content">
  
        {if (isset($elements['AGE_STAGGERed_roles_result']))}
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['AGE_STAGGERed_roles']}   
                   {include 'sys-template-parts/form.description.tpl' data=$elements['AGE_STAGGERed_roles_result']}                       
                </div>
            </div>
        {/if}  
        
        {if (isset($elements['role_membership_AGE_STAGGERed_roles_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_AGE_STAGGERed_roles']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_AGE_STAGGERed_roles_result']}                       
                </div>
            </div>
        {/if}  
        
        {if (isset($elements['role_membership_duty_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_duty']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_duty_result']}                       
                </div>
            </div>
        {/if}    
        
        {if (isset($elements['role_membership_exclusion_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_exclusion']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['role_membership_exclusion_result']}                       
                </div>
            </div>
        {/if}  
        
        {if (isset($elements['family_roles_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['family_roles']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['family_roles_result']}                       
                </div>
            </div>
        {/if}
          
        {if (isset($elements['account_details_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['account_details']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['account_details_result']}                       
                </div>
            </div>
        {/if} 
         
        {if (isset($elements['mandate_management_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['mandate_management']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['mandate_management_result']}                       
                </div>
            </div>
        {/if}  
          
        {if (isset($elements['iban_check_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_IBANCHECK')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['iban_check']}   
                    {include 'sys-template-parts/form.description.tpl' data=$elements['iban_check_result']}                       
                </div>
            </div>
        {/if} 
         
        {if (isset($elements['bic_check_result']))}   
            <div class="card admidio-field-group">
                <div class="card-header">{$l10n->get('PLG_MEMBERSHIPFEE_BICCHECK')}</div>
                <div class="card-body">
                    {include 'sys-template-parts/form.description.tpl' data=$elements['bic_check_result']}                       
                </div>
            </div>
        {/if} 
         
    </div>   
</form>
