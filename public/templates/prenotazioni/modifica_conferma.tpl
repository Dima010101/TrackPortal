{extends file='layouts/page.tpl'}

{block name=body}
{assign var='pid' value=$pren.id|default:0}
{assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Prenotazioni','url'=>'/prenotazioni'],['label'=>'Esito modifica']]}
    {include file='partials/breadcrumb.tpl'}
    <div class="page-header">
        <div>
            <h1>{if $success}Modifica confermata{else}Modifica non riuscita{/if}</h1>
            <p class="lead">
                {if $success}
                    La prenotazione è stata aggiornata correttamente.
                {else}
                    Non è stato possibile completare l'operazione.
                {/if}
            </p>
        </div>
    </div>

    {if $success}
        <div class="notification is-success tp-flash mb-3">
            {flash_icon type='ok'}
            <span>Modifiche salvate con successo.</span>
        </div>

        {valuta_switcher}

        <div class="card tp-panel-medium">
            <h3 class="card-title">Riepilogo aggiornato</h3>
            <dl class="confirm-panel-details">
                <div class="confirm-panel-detail">
                    <dt>Codice</dt>
                    <dd>{$pren.codice_identificativo|default:''|escape}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Importo</dt>
                    <dd>{prezzo value=$pren.prezzo_importo|default:0 currency=$pval}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Assicurazione</dt>
                    <dd>{if $pren.assicurazione}Sì{else}No{/if}</dd>
                </div>
                {if empty($pren.veicolo_noleggio_id)}
                    <div class="confirm-panel-detail">
                        <dt>Targa</dt>
                        <dd>{$pren.targa_veicolo|default:''|escape}</dd>
                    </div>
                {/if}
            </dl>
            <div class="btn-group mt-3">
                <a class="button is-primary" href="{url path="/prenotazioni/{$pid}"}">
                    {icon name='eye'} Dettaglio
                </a>
                <a class="button is-dark" href="{url path='/prenotazioni'}">
                    {icon name='history'} Torna all'elenco
                </a>
            </div>
        </div>
    {else}
        {foreach from=$errors|default:[] item=err}
            <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
        {/foreach}
        <a class="button is-primary" href="{url path='/prenotazioni'}">
            {icon name='arrow-left'} Torna all'elenco
        </a>
    {/if}
</div>
{/block}
