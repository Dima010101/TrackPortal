{extends file='layouts/page.tpl'}

{block name=body}
{assign var='vid' value=$form.veicolo_id|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Modifica veicolo']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Modifica veicolo</h1>
            {if $circuito}
                <p class="lead">{$circuito.nome_circuito|default:''|escape}</p>
            {/if}
        </div>
    </div>

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {if $veicolo && $circuito}
        <div class="card tp-panel-narrow">
            <form method="post" action="{url path="/flotta/veicolo/`$vid`/modifica"}" class="form-stack is-wide">
                {csrf_field}
                <input type="hidden" name="veicolo_id" value="{$vid}">
                {include file='partials/form_veicolo.tpl'}
                <button class="button is-primary" type="submit">{icon name='check'} Salva modifiche</button>
            </form>
        </div>
    {elseif $errors|@count == 0}
        <p class="text-muted">Veicolo non disponibile.</p>
        <a class="button is-dark" href="{url path='/flotta'}">Torna ai circuiti</a>
    {/if}
</div>
{/block}
