{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Aggiungi circuito']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Aggiungi circuito</h1>
            <p class="lead">Inserisci i dati del nuovo tracciato. I campi contrassegnati sono obbligatori.</p>
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

    <div class="card tp-panel-medium">
        <form method="post" action="{url path='/circuitiGestore/nuovo'}" class="form-stack is-wide"
              enctype="multipart/form-data">
            {csrf_field}
            {include file='partials/form_circuito.tpl'}
            <div class="flex">
                <button class="button is-primary" type="submit">{icon name='plus'} Conferma aggiunta</button>
                <a class="button is-dark" href="{url path='/circuitiGestore'}">Annulla</a>
            </div>
        </form>
    </div>
</div>
{/block}
