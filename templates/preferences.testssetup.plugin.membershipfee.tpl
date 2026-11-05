<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {$l10n->get('PLG_MEMBERSHIPFEE_TESTS_SETUP_INFO')}
    <br><br> 
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['age_staggered_roles']}
    <hr />
    
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['role_membership_age_staggered_roles']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['age_staggered_roles_cust_cont']}
        
    {if count($altersrollen) !== 0}
        {foreach $pPreferences->config.Altersrollen.altersrollen_token as $token}       
            {include 'sys-template-parts/form.checkbox.tpl' data=$elements["age_staggered_roles_exclusion$token"]}                 
        {/foreach}
    {else} 
        <div class="offset-sm-3">
            {$l10n->get('PLG_MEMBERSHIPFEE_NO_CONTRIBUTION_ROLES')}
        </div>
    {/if}
    <hr />
    
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['role_membership_duty_and_exclusion']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['role_membership_duty_cust_cont']}         
        
    {if count($altersrollen) !== 0 || count($familienrollen) !== 0 || count($fixrollen) !== 0}
        {if count($altersrollen) !== 0}
            {foreach $pPreferences->config.Altersrollen.altersrollen_token as $token}       
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements["altersrollenpflicht$token"]}                 
            {/foreach}
        {/if}
        {if count($familienrollen) !== 0}
            {include 'sys-template-parts/form.checkbox.tpl' data=$elements['familienrollenpflicht']}                 
        {/if}
        {if count($fixrollen) !== 0}
            {foreach $fixrollen as $key => $data} 
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements["fixrollenpflicht$key"]}                 
            {/foreach}
        {/if}
    {else} 
        <div class="offset-sm-3">
            {$l10n->get('PLG_MEMBERSHIPFEE_NO_CONTRIBUTION_ROLES')}
        </div>
    {/if}
       
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['role_membertship_exclusion_cust_cont']} 
    {if (count($altersrollen) !== 0 && count($familienrollen) !== 0 ) || count($fixrollen) !== 0}
        {if count($pPreferences->config.Altersrollen.altersrollen_token) !== 0 && isset($conf_array.altersrollenaltersrollen)}
            {foreach $conf_array.altersrollenaltersrollen as $key => $conf}
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements[{$conf}]}  
            {/foreach}
        {/if}
        {if count($altersrollen) !== 0 && count($familienrollen) !== 0}
            {foreach $pPreferences->config.Altersrollen.altersrollen_token as $token} 
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements["altersrollenfamilienrollen$token"]}                 
            {/foreach}
        {/if}
                
        {if count($familienrollen) !== 0 && count($fixrollen) !== 0} 
            {foreach $fixrollen as $key => $data} 
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements["familienrollenfix$key"]}                 
            {/foreach}
        {/if}
                
        {if count($fixrollen) !== 0} 
            {foreach $conf_array.fixrollenfixrollen as $key => $conf}
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements[{$conf}]}  
            {/foreach}
        {/if}
    {else} 
        <div class="offset-sm-3">
            {$l10n->get('PLG_MEMBERSHIPFEE_NO_CONTRIBUTION_ROLES')}
        </div>
    {/if}
    {include 'sys-template-parts/form.select.tpl' data=$elements['bezugskategorie']}                 
    <hr />
    
    {if ($num_familyroles !== 0)   }
        {include 'sys-template-parts/form.checkbox.tpl' data=$elements['family_roles']}                 
        {include 'sys-template-parts/form.custom-content.tpl' data=$elements['family_roles_cust_cont']}         
          
        {for $conf =0 to $num_familyroles-1}
            <i>{$conf+1}. {$l10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLE')}</i>
            {include 'sys-template-parts/form.input.tpl' data=$elements["familienrollen_pruefung$conf"]}           
        {/for}
        <hr />
    {/if}
    
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['account_details']}     
    <hr />
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['mandate_management']}              
    <hr />
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['iban_check']}              
    <hr />
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['bic_check']}                              
    <hr />
                                  
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_testssetup']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}
