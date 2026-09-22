<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="table-responsive">
        <table id="table_payments" class="{$classTable}" style="max-width: 100%;">
            <thead>
                <tr>
                    {foreach $headers as $key => $header}
                       <th style="text-align:{$columnAlign[$key]}">{$header}</th>
                    {/foreach}
                </tr>
            </thead>
            
            <tbody>          
                {if count($rows) eq 0}
                    <tr>
                        <td colspan="{count($headers)}" style="text-align: center;">{$l10n->get('SYS_NO_DATA_FOUND')}</td>
                    </tr>
                {else}
                    {foreach $rows as $row}
                        <tr id="{$row.id}"  >
                            {foreach $row.data as $key => $cell}
                                <td style="text-align:{$columnAlign[$key]}">{$cell}</td>
                            {/foreach}
                        </tr>
                    {/foreach} 
                {/if} 
            </tbody>
        </table>
    </div>   
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 