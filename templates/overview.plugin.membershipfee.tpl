<div id="plugin-{$name}" class="admidio-plugin-content">
    <h3>{$l10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE')}</h3>

    {if isset($overview_no_data)}
        <div class="admidio-form-group mb-3">
            <div>
                <strong>{$overview_no_data}</strong>
            </div>
        </div>
    {else}
         {if {$valid_login}}
            <div class="admidio-form-group mb-3">
                <div>
                    {$l10n->get('PLG_MEMBERSHIPFEE_FEE')}
                </div>
                <div>
                    <strong>{$overview_fee}</strong>
                </div>
            </div>
    
            <div class="admidio-form-group mb-3">
                <div>
                    {$l10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT')}
                </div>
                <div>
                    <strong>{$overview_contributory_text}</strong>
                </div>
            </div>
    
            <div class="admidio-form-group mb-3">
                <div>
                    {$l10n->get('PLG_MEMBERSHIPFEE_PAID_ON')}
                </div>
                <div>
                    <strong>{$overview_paid}</strong>
                </div>
            </div>
    
             <div class="admidio-form-group mb-3">
                <div>
                    {$l10n->get('PLG_MEMBERSHIPFEE_DUEDATE')}
                </div>
                <div>
                    <strong>{$overview_duedate}</strong>
                </div>
            </div>
        {else}
            <div>
                <strong>{$l10n->get('PLG_MEMBERSHIPFEE_OVERVIEW_NOVALIDLOGIN')}</strong>
            </div>
        {/if}
    {/if} 
</div>
