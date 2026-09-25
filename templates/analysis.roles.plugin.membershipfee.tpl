<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="card admidio-field-group">
        <p><h3>{$l10n->get('PLG_MEMBERSHIPFEE_ROLES_CONTRIBUTION')}</h3></p>
        <div class="table-responsive">
            <table id="table_roles_contribution" class="{$classTable}" style="max-width: 100%;">
                <thead>
                    <tr>
                        {foreach $headers as $key => $header}
                            <th style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$header}</th>
                        {/foreach}
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
        <p><strong>{$l10n->get('SYS_NOTE')}:</strong> {$l10n->get('PLG_MEMBERSHIPFEE_ROLES_CONTRIBUTION_DESC')}</p>
    </div>   
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 