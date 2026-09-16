<div class="table-responsive">          
    <table id="table_print_remapping" class="{$classTable}" {foreach $attributes as $attribute} {$attribute@key}="{$attribute}" {/foreach}style="max-width: 100%;">
        <thead>
            <tr>
                {foreach $headers as $key => $header}
                    <th style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$header}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody>           
            {if count($rows) eq 0}
                <tr>
                    <td colspan="{count($headers)}" style="text-align: center;">{$l10n->get('SYS_NO_MATCHING_ENTRIES')}</td>
                </tr>
            {else}
                {foreach $rows as $row}
                    <tr id="{$row.id}"  >
                        {foreach $row.data as $key => $cell}
                            <td style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$cell}</td>
                        {/foreach}
                    </tr>
                {/foreach}
            {/if} 
        </tbody>
    </table>
</div>   
           
