{include file="User/menu.tpl"}
<div class="alert alert-info">
    <p>
        {gt text="Create a zip or tar.gz of your module or theme extension. The archive should contain the extension's folder."}<br />
        {gt text="In this example we will used a module called HelloWorld. The archive would contain the module files in the folder"} <strong>HelloWorld</strong>
        {gt text="just as you would expect it to appear in your modules/ folder."}<br />
        {gt text='This module will attempt to extract a POT file from %1$s, %2$s and %3$s type extensions.' tag1='<strong>Core-1.2</strong>' tag2='<strong>Core-1.3</strong>' tag3='<strong>Core-1.4</strong>'}
    </p>
    <pre>
        HelloWorld/
        HelloWorld/Controller/UserController.php
        HelloWorld/Resources/views/User/main.tpl
    </pre>
    <p>{gt text="This utility will unpack this, extract the gettext strings and provide a .zip download with .pot file."}</p>
    <p>{gt text="The completed .zip will contain the the POT file in /locale"}</p>
    <pre>
        locale/module_helloworld.pot - {gt text="template of all extracted translation keys"}
    </pre>
    <p>
        {gt text="This service is provided free of charge and carries NO WARRANTY. Once your files are uploaded to the server, they are IMMEDIATELY processed and deleted.  Your files are not stored on the server."}
        {gt text="By using this script you explicitly release us from any liability and you grant us use of the files for the express purpose of extracting the gettext string after which point the module or theme files are deleted."}
    </p>
</div>

{insert name="getstatusmsg"}

<form class="form form-horizontal" action="{modurl modname=$module type='user' func='extract'}" method="post" enctype="multipart/form-data">
    <fieldset>
        <input type="hidden" name="authid" value="{insert name='generateauthkey' module=$module}" />
        <legend>{gt text="%s Extractor" tag1='.POT'}</legend>
        <div class="form-group">
            <label for="mtype" class="col-lg-3 control-label">{gt text="Component Type"}</label>
            <div class="col-lg-9" id="mtype">
                <div class="radio">
                    <input id="mtype1" type="radio" name="mtype" value="theme"{if $mtype|default:'module' eq 'theme'} checked="checked"{/if} />
                    <label for="mtype1">{gt text="Theme" }</label>
                </div>
                <div class="radio">
                    <input id="mtype0" type="radio" name="mtype" value="module"{if $mtype|default:'module' eq 'module'} checked="checked"{/if} />
                    <label for="mtype0">{gt text="Module" }</label>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="component" class="col-lg-3 control-label">{gt text="Name"}</label>
            <div class="col-lg-9">
                <input type="text" name="component" id="component" value="" class="form-control" />
                <em class="help-block">{gt text="Module or Theme Name (case sensitive exactly as module/theme folder)"}</em>
            </div>
        </div>
        <div class="form-group">
            <label for="archive" class="col-lg-3 control-label">{gt text="zip/tgz file"}</label>
            <div class="col-lg-9">
                <input type="file" name="archive" id="archive" size="50" maxlength="255" />
            </div>
        </div>
    </fieldset>

    <div class="col-lg-offset-3 col-lg-9">
        <button class="btn btn-success" type="submit" value=1 name="submit"><i class='fa fa-gear fa-lg'></i> {gt text="Generate"}</button>
        <a class="btn btn-danger" href="{modurl modname=$module type='user' func='extract'}" title="{gt text="Cancel"}"><i class='fa fa-times fa-lg'></i> {gt text="Cancel"}</a>
    </div>
</form>