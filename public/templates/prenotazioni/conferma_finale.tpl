{extends file='layouts/page.tpl'}

{block name=body}
{assign var='codice' value=$pren.codice_identificativo|default:''}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Prenotazione confermata']]}
    {include file='partials/breadcrumb.tpl'}
    <div class="hero">
        <h1>{icon name='check'} Prenotazione confermata</h1>
        <p class="lead">La tua prenotazione è stata registrata con successo.</p>
    </div>

    {valuta_switcher}

    <div class="card form-stack tp-panel-medium">
        <h3 class="card-title">Riepilogo finale</h3>
        <p><strong>Codice prenotazione:</strong> {$codice|escape}</p>
        {if $circuito}
            <p><strong>Circuito:</strong> {$circuito.nome_circuito|default:''|escape}</p>
        {/if}
        {if $sessione}
            <p><strong>Sessione:</strong> {$sessione.inizio|default:''|escape} → {$sessione.fine|default:''|escape}</p>
        {/if}
        {if !empty($pren.numero_box)}
            <p><strong>Box assegnato:</strong> n. {$pren.numero_box|escape}</p>
        {/if}
        <p><strong>Importo:</strong>
            {prezzo value=$pren.prezzo_importo|default:0 currency=$pren.prezzo_valuta|default:'EUR'}</p>
        <p><strong>Stato:</strong> <span class="badge {$pren.stato|default:'confermata'}">{stato_label stato=$pren.stato|default:'confermata'}</span></p>

        <div class="flex mt-3">
            <a class="button is-primary" href="{url path='/prenotazioni'}">
                {icon name='history'} Le mie prenotazioni
            </a>
            {if !empty($pren.id)}
                <a class="button is-dark" href="{url path="/prenotazioni/{$pren.id|default:0}"}">
                    {icon name='eye'} Dettaglio
                </a>
            {/if}
        </div>
    </div>
</div>
{/block}
