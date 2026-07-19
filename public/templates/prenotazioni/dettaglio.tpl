{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    {capture assign='bc_pren'}{if !empty($pren.codice_identificativo)}{$pren.codice_identificativo}{else}#{$pren.id|default:0}{/if}{/capture}
    {assign var='crumbs' value=[['label'=>'Prenotazioni','url'=>'/prenotazioni'],['label'=>$bc_pren]]}
    {include file='partials/breadcrumb.tpl'}

    {assign var='pst' value=$pren.stato|default:''}
    {assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}
    {assign var='pid' value=$pren.id|default:0}
    <div class="page-header">
        <div>
            <h1>Prenotazione {$pren.codice_identificativo|default:''}</h1>
            {if $pst == 'cancellata'}
                <span class="badge annullata">Annullata</span>
            {elseif !$attiva|default:false}
                <span class="badge terminata">Terminata</span>
            {else}
                <span class="badge confermata">Confermata</span>
            {/if}
        </div>
        {if $attiva|default:false}
            <div class="btn-group">
                <a class="button is-primary" href="{url path="/prenotazioni/{$pid}/modifica"}">
                    {icon name='settings'} Modifica
                </a>
            </div>
        {/if}
    </div>

    {valuta_switcher}

    <div class="detail-grid detail-grid--booking">
        <div class="card">
            <h3 class="card-title">Fascia oraria e circuito</h3>
                <p><strong>Circuito:</strong>
                   {assign var='pcid' value=$pren.circuito_id|default:0}
                   <a href="{url path="/circuiti/{$pcid}"}">{$pren.nome_circuito|default:''}</a>
                </p>
                <p><strong>Inizio disponibilità:</strong> {date_pretty sql=$pren.inizio_sessione|default:''}</p>
                <p><strong>Fine disponibilità:</strong> {date_pretty sql=$pren.fine_sessione|default:''}</p>
                {if !empty($pren.numero_box)}
                    <p><strong>Box assegnato:</strong> n. {$pren.numero_box|escape}</p>
                {/if}

                <hr class="divider">

                <h3 class="card-title">Veicolo</h3>
                {if !empty($pren.veicolo_noleggio_id)}
                    <p>Noleggio: <strong>{$pren.v_marca|default:''} {$pren.v_modello|default:''}</strong>
                       ({$pren.v_targa|default:''})</p>
                {else}
                    <p>Veicolo proprio · targa: <strong>{$pren.targa_veicolo|default:''}</strong></p>
                {/if}

                <p><strong>Assicurazione:</strong> {if $pren.assicurazione}Si{else}No{/if}</p>
                {if !empty($pren.promozione_titolo)}
                    <p><strong>Promozione applicata:</strong> {$pren.promozione_titolo|default:''}</p>
                    {if !empty($pren.promozione_descrizione)}
                        <p class="text-muted promo-applied-desc">{$pren.promozione_descrizione|escape|nl2br nofilter}</p>
                    {/if}
                    <p><strong>Sconto applicato:</strong> {prezzo value=$pren.sconto_importo|default:0 currency=$pval}</p>
                {/if}

                {if $attiva|default:false}
                    <div class="cancel-section">
                        <div class="cancel-section-icon">{icon name='alert' size=22}</div>
                        <div class="cancel-section-body">
                            <h3 class="cancel-section-title">Annulla prenotazione</h3>
                            <p class="text-muted mb-0">Riceverai un rimborso del <strong>{$rimborso_perc|default:70}%</strong> dell'importo pagato ({prezzo value=$rimborso_stimato|default:0 currency=$pval}){if !$pren.assicurazione} · con l'assicurazione sarebbe il 100%{/if}.</p>
                        </div>
                        <a href="{url path="/prenotazioni/{$pid}/cancella"}"
                           class="button is-danger cancel-section-action">
                            {icon name='trash'} Procedi con l'annullamento
                        </a>
                    </div>
            {/if}
        </div>

        <aside class="card">
            <h3 class="card-title">Ricevuta</h3>
            <p><strong>Importo:</strong> {prezzo value=$pren.prezzo_importo|default:0 currency=$pval}</p>
            {if !empty($pren.codice_identificativo)}
                <p><strong>Documento:</strong> <code>{$pren.codice_identificativo}</code></p>
            {/if}
            {if $pst == 'cancellata'}
                <hr class="divider">
                <p><strong>Rimborso previsto:</strong> {prezzo value=$pren.rimborso_previsto|default:0 currency=$pval}</p>
                <p class="text-muted mb-0">{$pren.causa_cancellazione|default:''}</p>
            {/if}
            <hr class="divider">
            <p class="text-dim mb-3">Creata il {date_pretty sql=$pren.data_inserimento|default:''}</p>
            <a class="button is-primary is-fullwidth" href="{url path="/prenotazioni/{$pid}/fattura"}">
                {icon name='receipt'} Scarica ricevuta PDF
            </a>

            {if $fatture|default:[]}
                <hr class="divider">
                <p class="text-dim mb-2"><strong>Fatture</strong></p>
                {assign var='fatt_labels' value=[
                    'gestore_circuiti' => 'Fattura circuito',
                    'azienda_noleggio' => 'Fattura noleggio',
                    'piattaforma'      => 'Fattura assicurazione'
                ]}
                {foreach from=$fatture item=f}
                    <a class="button is-link is-fullwidth mt-2" href="{url path="/prenotazioni/documento/{$f.id}"}"
                       title="Documento {$f.numero_formattato|default:''}">
                        {icon name='receipt'} {$fatt_labels[$f.emittente_tipo]|default:'Fattura PDF'}
                    </a>
                {/foreach}
            {/if}
        </aside>
    </div>
</div>
{/block}
