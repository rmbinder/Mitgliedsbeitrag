<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="table-responsive">
        <table id="{$tableId}" class="{$classTable}" style="max-width: 100%;">
        
            <thead>
                <tr>
                    {foreach $headers as $key => $header}
                        <th style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$header}</th>
                    {/foreach}
                </tr>
            </thead>
            
            {if count($rows) !== 0}
                <tbody>          
                    {foreach $rows as $row}
                        <tr id="{$row.id}"  >
                            {foreach $row.data as $key => $cell}
                                <td style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$cell}</td>
                            {/foreach}
                        </tr>
                    {/foreach} 
                </tbody>
            {/if} 
        </table>
    </div>   
    
    {if $mode == 'preview'}       
        {if count($rows) !== 0}
            <p>{$l10n->get('PLG_MEMBERSHIPFEE_REMAPPING_PREVIEW')}</p>  
            {include 'sys-template-parts/form.button.tpl' data=$elements['btn_next_page']}      
        {/if} 
    {elseif $mode == 'save'}
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_REMAPPING_SAVED')}</p> 
    {/if} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 