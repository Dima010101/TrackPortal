{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Convalida','url'=>'/affiliazioni'],['label'=>'Esito operazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="notification is-danger">
        <ul class="tp-error-list">
            {foreach from=$errors item=err}
                <li>{$err|escape}</li>
            {/foreach}
        </ul>
    </div>

    <a class="button is-primary" href="{url path='/affiliazioni'}">
        Torna alle richieste
    </a>
</div>
{/block}
