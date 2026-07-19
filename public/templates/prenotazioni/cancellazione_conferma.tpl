{extends file='layouts/page.tpl'}

{block name=body}
{assign var='pid' value=$pren.id|default:0}
{assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Prenotazioni','url'=>'/prenotazioni'],['label'=>'Esito cancellazione']]}
    {include file='partials/breadcrumb.tpl'}
    <div class="page-header">
        <div>
            <h1>{if $success}Cancellazione confermata{else}Cancellazione non riuscita{/if}</h1>
            <p class="lead">
                {if $success}
                    La prenotazione è stata annullata correttamente.
                {else}
                    Non è stato possibile completare l'operazione.
                {/if}
            </p>
        </div>
    </div>

    {if $success}
        <div class="notification is-success tp-flash mb-3">
            {flash_icon type='ok'}
            <span>Prenotazione cancellata. Rimborso previsto: {prezzo value=$rimborso|default:0 currency=$pval} ({$rimborso_perc|default:70}% dell'importo).</span>
        </div>

        {valuta_switcher}

        <div class="card tp-panel-medium">
            <h3 class="card-title">Riepilogo</h3>
            <dl class="confirm-panel-details">
                <div class="confirm-panel-detail">
                    <dt>Codice</dt>
                    <dd>{$pren.codice_identificativo|default:''|escape}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Rimborso previsto</dt>
                    <dd>{prezzo value=$rimborso|default:0 currency=$pval} ({$rimborso_perc|default:70}%)</dd>
                </div>
            </dl>
            <a class="button is-primary mt-3" href="{url path='/prenotazioni'}">
                {icon name='history'} Torna all'elenco
            </a>
        </div>
    {else}
        {foreach from=$errors|default:[] item=err}
            <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
        {/foreach}
        <a class="button is-primary" href="{url path="/prenotazioni/{$pid}/cancella"}">
            {icon name='arrow-left'} Riprova
        </a>
    {/if}
</div>
{/block}
