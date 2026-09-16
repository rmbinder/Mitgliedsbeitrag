<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    <div class="table-responsive">
        <table id="table_preview_individualcontributions" class="{$classTable}" style="max-width: 100%;">
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
                        <td colspan="{count($headers)}" style="text-align: center;">{$l10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_NO_DATA')}</td>
                       
                        {* Wenn die nächsten 5 Data-Zeilen nicht vorhanden sind, wird seltsamerweise nachfolgender Fehler ausgeworfen: *}                  
                        {* DataTables warning: table id=table_preview_recalculation - Requested unknown parameter '1' for row 0, column 1....  *}
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
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
           
    {if count($rows) !== 0}
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_PREVIEW')}</p>  
        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_next_page']}      
    {/if} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 