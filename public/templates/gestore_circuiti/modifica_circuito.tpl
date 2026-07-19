{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Modifica circuito']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Modifica circuito</h1>
            <p class="lead">Aggiorna i dati del tracciato, aggiungi nuove foto o rimuovi quelle esistenti.</p>
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
        <form method="post" action="{url path="/circuitiGestore/`$circuito_id`/modifica"}"
              class="form-stack is-wide" enctype="multipart/form-data">
            {csrf_field}
            {include file='partials/form_circuito.tpl'}

            {if !empty($foto)}
                <div class="form-row">
                    <label>Foto attuali</label>
                    <p class="text-muted text-small">
                        Spunta &laquo;Rimuovi&raquo; sulle foto da eliminare; la rimozione avverrà al salvataggio.
                    </p>
                    <div class="tp-foto-edit-grid">
                        {foreach from=$foto item=f}
                            <figure class="tp-foto-edit">
                                <img src="{url path=$f.path_file}"
                                     alt="{$f.didascalia|default:''|escape}" loading="lazy">
                                {if !empty($f.didascalia)}
                                    <figcaption class="text-small">{$f.didascalia|escape}</figcaption>
                                {/if}
                                <label class="tp-foto-remove-check">
                                    <input type="checkbox" name="rimuovi_foto[]" value="{$f.id}">
                                    {icon name='trash' size=14} Rimuovi
                                </label>
                            </figure>
                        {/foreach}
                    </div>
                </div>
            {/if}

            <div class="flex">
                <button class="button is-primary" type="submit">{icon name='check'} Salva modifiche</button>
                <a class="button is-dark" href="{url path="/circuiti/`$circuito_id`"}">Annulla</a>
            </div>
        </form>
    </div>
</div>
{/block}
