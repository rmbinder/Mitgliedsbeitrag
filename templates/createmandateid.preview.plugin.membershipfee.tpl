<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    <div class="table-responsive">
        <table id="table_preview_createmandateid" class="{$classTable}" style="max-width: 100%;">
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
                        <td colspan="{count($headers)}" style="text-align: center;">{$l10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_NO_ASSIGN')}</td>
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
           
    {if !$errorMarker && count($rows) !== 0}
        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_next_page']}
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_PREVIEW')}</p>        
    {/if} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 