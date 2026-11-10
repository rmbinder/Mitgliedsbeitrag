<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="table-responsive">
        <table id="table_pre_notification" class="{$classTable}" style="max-width: 100%;">
        
            <thead>
                <tr>
                    {foreach $headers as $key => $header}
                       <th style="text-align:{$columnAlign[$key]}">{$header}</th>
                    {/foreach}
                </tr>
            </thead>
            
            {if count($rows) !== 0}
                <tbody>          
                    {foreach $rows as $row}
                        <tr id="{$row.id}"  >
                            {foreach $row.data as $key => $cell}
                                <td style="text-align:{$columnAlign[$key]}">{$cell}</td>
                            {/foreach}
                        </tr>
                    {/foreach} 
                </tbody>
            {/if} 
        </table>
    </div>   
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 