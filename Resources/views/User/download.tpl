{include file="User/menu.tpl"}
<h2>{gt text="Gettext Key Extractor" }</h2>
<pre>
    DEBUG OUTPUT:
    {$output}
</pre>
<hr />
{if $result=='0'}
<div class="alert alert-success">
    <h2>{gt text="Extraction complete!"}</h2>
    <a class="btn btn-lg btn-success" href="{route name='zikulagettextmodule_user_download' key=$key c=$c d=$d}"><i class='fa fa-cloud-download fa-2x'></i> {gt text="Download"}</a>
</div>
{else}
<div class="alert alert-danger">
    <h2>{gt text="Extraction failed!"}</h2>
    {gt text="Unable to extract POT file due to problems displayed above."}<br />
    <a href="{route name='zikulagettextmodule_user_extract'}">{gt text="Go back"}</a>
</div>
{/if}
