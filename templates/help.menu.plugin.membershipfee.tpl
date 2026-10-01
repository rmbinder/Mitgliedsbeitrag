<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
    {foreach $elements as $element}
        {if $element.type == 'description'}
            {include 'sys-template-parts/form.description.tpl' data=$element}
        {/if}
        {if $element.type == 'custom-content'}
            {include 'sys-template-parts/form.custom-content.tpl' data=$element}
        {/if}
    {/foreach}
    
</form>
