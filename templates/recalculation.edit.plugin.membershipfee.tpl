<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    
     <p><h3>{$headline}</h3></p>        
    <p>{$username}</p> 
           
    <hr />
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['fee']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['fee_new']}
    
    <hr />
    
    {include 'sys-template-parts/form.input.tpl' data=$elements['contributory_text']}
    {include 'sys-template-parts/form.input.tpl' data=$elements['contributory_text_new']}              
      
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_save_configurations']}
    <div class="form-alert" style="display: none;">&nbsp;</div>
</form>
