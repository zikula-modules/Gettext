{include file="User/menu.tpl"}
<h2>{gt text="Gettext Key Extractor" }</h2>
<br />
<hr />
<pre>
    DEBUG OUTPUT:
    {$output}
</pre>
<hr />
{if $result=='0'}
<p class="alert alert-info">
    {gt text="Success!"} <a class="btn btn-success" href="{modurl modname=$module type='user' func='download' key=$key c=$c d=$d}"><i class='fa fa-cloud-download fa-lg'></i> {gt text="download"}</a>
</p>
{else}
<p class="alert alert-danger">
    {gt text="Unable to extract POT file due to problems displayed above."}<br />
    <a href="{modurl modname='Gettext' type='user' func='extract'}">{gt text="Go back"}</a>
</p>
{/if}
<hr />
<br />
