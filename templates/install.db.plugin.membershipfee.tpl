<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {if isset($elements['adm_csrf_token'])}
        {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']} 
    {/if} 
   
    <p>{$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_DESCRIPTION')}</p>        
   
    {if $mode == 'start' || $mode == 'anlegen'} 
        <p><strong>{$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_FIRST_PASSAGE')}: ==> {$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_VERIFICATION_MISSING_FIELDS')}</strong></p>  
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_SECOND_PASSAGE')}: {$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_VERIFICATION_COMPARISON')}</p> 
        <br/>

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
                                {*      <td {if isset($row.colspan)}colspan="{$row.colspan}"{/if} style="text-align:{$columnAlign[$key]};">{$cell}</td>         *}
                                {/foreach}
                            </tr>
                        {/foreach} 
                    </tbody>
                {/if}
             
            </table>
        </div>   
     
        {if isset($elements['fields_should_be_created'])}      
            {include 'sys-template-parts/form.description.tpl' data=$elements['fields_should_be_created']}
            {include 'sys-template-parts/form.button.tpl' data=$elements['btnAnlegen']}
            {include 'sys-template-parts/form.button.tpl' data=$elements['btnAbbrechen']}   
            <p><strong>{$l10n->get('SYS_NEXT')}</strong> {$l10n->get('PLG_MEMBERSHIPFEE_CREATE_MISSING_FIELDS')}</p>  
            <p><strong>{$l10n->get('PLG_MEMBERSHIPFEE_ABORT')}</strong> {$l10n->get('PLG_MEMBERSHIPFEE_NO_CHANGES')}</p>  
        {else}    
            <p>{$l10n->get('PLG_MEMBERSHIPFEE_ALL_FIELDS_ARE_AVAILABLE')}</p>  
            {include 'sys-template-parts/form.button.tpl' data=$elements['btnSollIst']}
            <p><strong>{$l10n->get('SYS_NEXT')}</strong> {$l10n->get('PLG_MEMBERSHIPFEE_CHANGE_NEXT_TEST')}</p>  
        {/if} 

    {else}                {* soll_ist *}
        <p>{$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_FIRST_PASSAGE')}: {$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_VERIFICATION_MISSING_FIELDS')}</p>  
        <p><strong>{$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_SECOND_PASSAGE')}: ==> {$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_VERIFICATION_COMPARISON')}</strong></p> 
        <br/>

        <div class="table-responsive">
            <table id="{$tableId}" class="{$classTable}" style="max-width: 100%;">
        
                <thead>
                    <tr>
                        <th rowspan="2" style="text-align:left; vertical-align: center; width:10%">{$l10n->get('SYS_CATEGORY')}</th> 
                        <th rowspan="2" style="text-align:left; vertical-align: center; width:10%">{$l10n->get('PLG_MEMBERSHIPFEE_PROFILE_FIELD')}</th> 
                        <th colspan="2" style="text-align:center;width:10%">{$l10n->get('SYS_INTERNAL_NAME')}</th> 
                        <th colspan="2" style="text-align:center;width:10%">{$l10n->get('PLG_MEMBERSHIPFEE_DATA_TYPE')}</th>  
                        <th colspan="2" style="text-align:center;width:5%">{$biEye}</th>  
                        <th colspan="2" style="text-align:center;width:5%">{$biKey}</th> 
                        <th colspan="2" style="text-align:center;width:5%">{$biAsterisk}</th>      
                    </tr>
                    <tr>
                        {for $i=0 to 4}       
                            <th style="text-align:center;width:5%">{$l10n->get('PLG_MEMBERSHIPFEE_SHALL')}</th>  
                            <th style="text-align:center;width:5%">{$l10n->get('PLG_MEMBERSHIPFEE_IS')}</th>         
                        {/for }       
                    </tr>
                </thead>
            
                {if count($rows) !== 0}
                    <tbody>          
                        {foreach $rows as $row}
                            <tr id="{$row.id}"  >
                                {foreach $row.data as $key => $cell}
                                    <td style="text-align:{$columnAlign[$key]};{if $columnWidth[$key] !== ''} width:{$columnWidth[$key]};{/if}">{$cell}</td>    
                                {/foreach}
                                
                                {if isset($row.subheader)}
                                    <td colspan="8"></td>  
                                {/if}
                            </tr>
                        {/foreach} 
                    </tbody>
                {/if}
             
            </table>
        </div>   

        <p>{$l10n->get('PLG_MEMBERSHIPFEE_SECOND_PASSAGE_INFO')}</p>  
        {include 'sys-template-parts/form.button.tpl' data=$elements['btnNext']}
        <p><strong>{$l10n->get('SYS_NEXT')}</strong> {$l10n->get('PLG_MEMBERSHIPFEE_INSTALL_END')}</p>  
    {/if}

    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
 