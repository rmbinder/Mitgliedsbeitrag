<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="card admidio-field-group">
        <p><h3>{$l10n->get('PLG_MEMBERSHIPFEE_MEMBERS_CONTRIBUTION')}</h3></p>
        <div class="table-responsive">
            <table id="table_members_contribution" class="{$classTable}" style="max-width: 100%;">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th colspan="2" style="text-align: right;">{$l10n->get('PLG_MEMBERSHIPFEE_WITH_ACCOUNT_DATA')}</th>                    
                        <th colspan="2" style="text-align: right;">{$l10n->get('PLG_MEMBERSHIPFEE_WITH_ACCOUNT_DATA')}</th>      
                        <th colspan="2" style="text-align: right;">{$l10n->get('PLG_MEMBERSHIPFEE_SUM')}</th>          
                    </tr>
                </thead>
            
                <tbody>          
                    {foreach $rows as $row}
                        <tr id="{$row.id}"  >
                            {foreach $row.data as $key => $cell}
                                <td style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$cell}</td>
                            {/foreach}
                        </tr>
                    {/foreach} 
                </tbody>
            </table>
        </div>   
        <p><strong>{$l10n->get('SYS_NOTE')}:</strong> {$l10n->get('PLG_MEMBERSHIPFEE_MEMBERS_CONTRIBUTION_DESC')}</p>
    </div>   
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 