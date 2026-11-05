<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>

        {$l10n->get('PLG_MEMBERSHIPFEE_EVENTS_SELECTION_INFO')}
        <br> <br> 
        {include 'sys-template-parts/form.select.tpl' data=$elements['events_selection']}
        {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_options']} 

    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
{$javascript}
