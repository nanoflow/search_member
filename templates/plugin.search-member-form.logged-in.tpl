<div id="plugin-{$name}" class="admidio-plugin-content">
    <h3>{$l10n->get('PLG_SEARCH_HEADLINE')}</h3>
    <form {foreach $attributes as $attribute}
            {$attribute@key}="{$attribute}"
        {/foreach}>
        {include 'sys-template-parts/form.input.tpl' data=$elements['plg_search_usr']}
        {include 'sys-template-parts/form.button.tpl' data=$elements['plg_btn_search']}
        <div class="form-alert" style="display: none;">&nbsp;</div>
    </form>
</div>
