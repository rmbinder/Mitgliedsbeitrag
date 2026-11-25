<div class="table-responsive">     
    <table id="table_membership_fee" class="{$classTable}" {foreach $attributes as $attribute} {$attribute@key}="{$attribute}" {/foreach}style="max-width: 100%;">
        <tbody>                        
            {foreach $rows as $row}
                <tr id="{$row.id}"  >
                    {foreach $row.data as $key => $cell}
                        <td style="text-align:{$columnAlign[$key]};">{$cell}</td>
                    {/foreach}
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>   

     
           
            
