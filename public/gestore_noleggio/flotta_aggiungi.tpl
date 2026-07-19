{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Aggiungi veicolo']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Aggiungi veicolo</h1>
            <p class="lead">Inserisci i dati del nuovo veicolo e associalo a un circuito. I campi contrassegnati sono obbligatori.</p>
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

    {if $circuiti|@count == 0}
        <div class="empty-state">
            {icon name='flag' size=56}
            <h3>Nessun circuito disponibile</h3>
            <p>Non risultano circuiti registrati sul sito a cui associare un veicolo.</p>
        </div>
    {else}
        <div class="card tp-panel-medium">
            <form method="post" action="{url path='/flotta/nuovo'}" class="form-stack is-wide">
                {csrf_field}
                {include file='partials/form_veicolo.tpl'}
                <div class="flex">
                    <button class="button is-primary" type="submit">{icon name='plus'} Conferma aggiunta</button>
                    <a class="button is-dark" href="{url path='/flotta'}">Annulla</a>
                </div>
            </form>
        </div>
    {/if}
</div>
{/block}
