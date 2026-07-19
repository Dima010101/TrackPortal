{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Promozioni','url'=>'/promozioni'],['label'=>'Modifica promozione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Modifica promozione</h1>
            {if $tipo_entita == 'veicolo'}
                <p class="lead">
                    Offerta per il veicolo
                    <strong>{$entita.marca|default:''} {$entita.modello|default:''}</strong>
                    ({$entita.targa|default:''})
                </p>
            {else}
                <p class="lead">
                    Offerta per il circuito <strong>{$entita.nome_circuito|default:''}</strong>
                </p>
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

    <div class="card tp-panel-wide">
        <form method="post"
              action="{url path="/promozioni/{$promo_id|default:0}/modifica"}"
              class="form-stack is-wide">
            {csrf_field}
            {include file='partials/form_promozione.tpl'}
            <div class="flex">
                <button class="button is-primary" type="submit">{icon name='check'} Salva modifiche</button>
                <a class="button is-dark"
                   href="{url path="/promozioni/servizio/{$entita_id|default:0}"}">Annulla</a>
            </div>
        </form>
    </div>
</div>
{/block}
