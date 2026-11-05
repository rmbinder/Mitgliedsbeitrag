<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

    {include 'sys-template-parts/form.input.tpl' data=$elements['beitrag_prefix']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['beitrag_suffix']}
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['beitrag_anteilig']}
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['beitrag_anteilig_desc2']}
  
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['beitrag_abrunden']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['beitrag_mindestbetrag']}

    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['beitrag_textmitnam']}
    {include 'sys-template-parts/form.checkbox.tpl' data=$elements['beitrag_textmitfam']}   
     
    {include 'sys-template-parts/form.select.tpl' data=$elements['beitrag_text_token']}   
    {include 'sys-template-parts/form.input.tpl' data=$elements['beitrag_role_separator']}
           
    {include 'sys-template-parts/form.custom-content.tpl' data=$elements['role_seperator_desc2']}
                 
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
