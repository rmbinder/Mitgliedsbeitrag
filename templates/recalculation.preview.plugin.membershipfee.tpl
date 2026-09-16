<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div class="row">
        <div class="col-sm-6 col-lg-4 col-xl-6" id="recalculation_roleselect">
            <div class="card admidio-card">
                <div class="card-body">
                    {include '../templates/form.select.popover.plugin.membershipfee.tpl' data=$elements['recalculation_roleselection'] popover="{$l10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_ROLLQUERY_DESC')}"}
                </div>
            </div>
        </div>
    
        <div class="col-sm-2 col-lg-4 col-xl-2" id="recalculation_notpaid">
            <div class="card admidio-card">
                <div class="card-body">
                    {include '../templates/form.radio.popover.plugin.membershipfee.tpl' data=$elements['recalculation_notpaid'] popover="{$l10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_NOT_PAID_DESC')}"}        
                </div>
            </div>    
        </div>
   
        <div class="col-sm-6 col-lg-4 col-xl-2" id="recalculation_mode">
            <div class="card admidio-card">
                <div class="card-body">
                    {include '../templates/form.radio.popover.plugin.membershipfee.tpl' data=$elements['recalculation_mode'] popover="{$l10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_MODUS_DESC')}"}             
                </div>
            </div>
        </div>
            
        <div class="col-sm-6 col-lg-4 col-xl-2" id="recalculation_button">
            <div class="card admidio-card">
                <div class="card-body">                                                              
                    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_recalculation']}   
                    {if count($rows) !== 0}
                        {include 'sys-template-parts/form.button.tpl' data=$elements['btn_next_page']}
                    {/if}           
                </div>
            </div>
        </div>
    </div>
                    
    <div class="table-responsive">
        <table id="table_preview_recalculation" class="{$classTable}" style="max-width: 100%;">
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
                        <td colspan="{count($headers)}" style="text-align: center;">{$l10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_NO_DATA')}</td>
                       
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
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_PREVIEW')}</p>        
    {/if} 
    
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 