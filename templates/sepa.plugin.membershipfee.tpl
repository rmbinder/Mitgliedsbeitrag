<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {if isset($elements['adm_csrf_token'])}
        {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']} 
    {/if} 
    
    <div class="table-responsive">
        <table id="table_sepa" class="{$classTable}" style="max-width: 100%;">
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
  
    {include 'sys-template-parts/form.description.tpl' data=$elements['selection_desc']} 
    {include 'sys-template-parts/form.radio.tpl' data=$elements['export_file_mode']} 
    
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save']} 
    {include 'sys-template-parts/form.description.tpl' data=$elements['selection_alert']} 
         
   
</form>
