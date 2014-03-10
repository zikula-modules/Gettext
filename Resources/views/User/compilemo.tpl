{include file="User/menu.tpl"}
{insert name="getstatusmsg"}
<form class="form form-horizontal" action="{modurl modname="Gettext" type="user" func="compilemo"}" method="post" enctype="multipart/form-data">
    <fieldset>
        <input type="hidden" name="authid" value="{insert name="generateauthkey" module="gettext"}" />
        <legend>{gt text="Gettext PO to MO Compiler"}</legend>
        <p class="alert alert-info">{gt text='This utility will compile a .po translation file into .mo'}</p>
        <div class="form-group">
            <label for="forcefuzzy" class="col-lg-3 control-label">{gt text="Include fuzzy matches"}</label>
            <div class="col-lg-9">
                <div class="checkbox">
                    <input name="forcefuzzy" type="checkbox" id="checkbox" value="1" />
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="po" class="col-lg-3 control-label">{gt text="Upload .po file"}</label>
            <div class="col-lg-9">
                <input type="file" name="po" id="po" size="50" maxlength="255" />
            </div>
        </div>
    </fieldset>

    <div class="col-lg-offset-3 col-lg-9">
        <button class="btn btn-success" type="submit" name="Save"><i class='fa fa-gear fa-lg'></i> {gt text="Compile"}</button>
        <a class="btn btn-danger" href="{modurl modname=$module type='admin' func='main'}" title="{gt text="Cancel"}"><i class='fa fa-times fa-lg'></i> {gt text="Cancel"}</a>
    </div>
</form>
