{include file="User/menu.tpl"}
<h2>{gt text="Gettext PO to MO Compiler" }</h2>
<br />
<div class="alert alert-success">
    <h2>{gt text="Compilation complete!"}</h2>
    <a class="btn btn-lg btn-success" href="{modurl modname=$module type='user' func='downloadmo' key=$key c=$c d=$d}"><i class='fa fa-cloud-download fa-2x'></i> {gt text="Download"}</a>
</div>
