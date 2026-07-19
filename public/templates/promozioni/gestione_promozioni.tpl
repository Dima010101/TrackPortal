{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Promozioni']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Promozioni</h1>
            <p class="lead">
                Seleziona {if $tipo_entita == 'veicolo'}un veicolo della tua flotta{else}uno dei tuoi circuiti{/if}
                per visualizzare e gestire le promozioni associate.
            </p>
        </div>
    </div>

    {if $empty_list|default:false}
        <div class="empty-state">
            {icon name='tag' size=56}
            <h3>Nessuna risorsa disponibile</h3>
            <p>
                {if $tipo_entita == 'veicolo'}
                    Non hai veicoli in flotta su cui creare promozioni.
                {else}
                    Non hai circuiti associati al tuo profilo.
                {/if}
            </p>
            {if $tipo_entita == 'veicolo'}
                <a class="button is-primary" href="{url path='/flotta'}">
                    {icon name='car'} Gestisci flotta
                </a>
            {elseif $can_create_circuiti|default:true}
                <a class="button is-primary" href="{url path='/circuitiGestore/nuovo'}">
                    {icon name='plus'} Aggiungi circuito
                </a>
            {else}
                <p class="text-muted">In attesa di approvazione amministrativa non puoi ancora registrare circuiti.</p>
            {/if}
        </div>
    {else}
        <div class="grid-cards">
            {foreach from=$entita item=e}
                {assign var='eid' value=$e.id|default:0}
                <div class="entity-card">
                    {if $tipo_entita == 'veicolo'}
                        <h3 class="entity-card-title">{$e.marca|default:''} {$e.modello|default:''}</h3>
                        <div class="text-muted text-small">
                            {icon name='car' size=12} {$e.targa|default:''}
                            {if !empty($e.nome_circuito)} · {$e.nome_circuito}{/if}
                        </div>
                    {else}
                        <h3 class="entity-card-title">{$e.nome_circuito|default:''}</h3>
                        <div class="text-muted text-small">
                            {icon name='home' size=12} {$e.localita|default:''}
                        </div>
                    {/if}
                    <div class="entity-card-footer">
                        <a class="button is-primary is-small"
                           href="{url path="/promozioni/servizio/{$eid}"}">
                            {icon name='tag'} Gestisci promozioni
                        </a>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}

    {assign var='is_veicolo' value=($tipo_entita == 'veicolo')}
    {assign var='riep_titolo' value=$is_veicolo ? 'Promozioni dei tuoi veicoli' : 'Promozioni dei tuoi circuiti'}
    {assign var='riep_sub' value=$is_veicolo ? 'Riepilogo delle promozioni attive e passate su tutti i veicoli della tua flotta.' : 'Riepilogo delle promozioni attive e passate su tutti i circuiti che gestisci.'}
    {assign var='riep_empty' value=$is_veicolo ? 'Non hai ancora creato promozioni sui tuoi veicoli.' : 'Non hai ancora creato promozioni sui tuoi circuiti.'}
    {assign var='riep_col' value=$is_veicolo ? 'Veicolo' : 'Circuito'}
    {assign var='tot_promo' value=($promo_attive|@count) + ($promo_passate|@count)}
    <div class="card mt-4 promo-riepilogo" data-promo-filter-root>
        <div class="tp-card-heading mb-3">
            <div class="promo-form-header mb-0">
                <div class="promo-form-icon">{icon name='tag' size=22}</div>
                <div>
                    <h3 class="card-title mb-0">{$riep_titolo}</h3>
                    <p class="text-muted text-small mb-0">
                        {$riep_sub}
                    </p>
                </div>
            </div>

            {if $tot_promo > 0}
                <div class="btn-group" role="group" aria-label="Filtra promozioni">
                    <button type="button" data-promo-filter="tutte" aria-pressed="true">
                        Tutte <span class="badge">{$tot_promo}</span>
                    </button>
                    <button type="button" data-promo-filter="attive" aria-pressed="false">
                        Attive <span class="badge confermata">{$promo_attive|@count}</span>
                    </button>
                    <button type="button" data-promo-filter="passate" aria-pressed="false">
                        Passate <span class="badge cancellata">{$promo_passate|@count}</span>
                    </button>
                </div>
            {/if}
        </div>

        {if $tot_promo == 0}
            <div class="empty-state">
                {icon name='tag' size=48}
                <h3>Nessuna promozione</h3>
                <p>{$riep_empty}</p>
            </div>
        {else}
            {foreach from=[['chiave' => 'attive', 'titolo' => 'Attive', 'lista' => $promo_attive, 'badge' => 'confermata'], ['chiave' => 'passate', 'titolo' => 'Passate', 'lista' => $promo_passate, 'badge' => 'cancellata']] item=sezione}
                <div data-promo-group="{$sezione.chiave}">
                    <h4 class="mt-3 mb-2">
                        {$sezione.titolo} <span class="badge {$sezione.badge}">{$sezione.lista|@count}</span>
                    </h4>
                    {if $sezione.lista|@count == 0}
                        <p class="text-muted text-small">Nessuna promozione {$sezione.titolo|lower}.</p>
                    {else}
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr>
                                    <th>Titolo</th><th>{$riep_col}</th><th>Codice</th><th>Sconto</th><th>Validità</th>
                                </tr></thead>
                                <tbody>
                                {foreach from=$sezione.lista item=p}
                                    <tr>
                                        <td><strong>{$p.titolo|default:''}</strong></td>
                                        <td>{if $is_veicolo}{$p.nome_veicolo|default:'—'}{else}{$p.nome_circuito|default:'—'}{/if}</td>
                                        <td><code>{$p.codice|default:''}</code></td>
                                        <td>
                                            {$p.valore|default:''}{if $p.tipo_sconto|default:'' == 'percentuale'}%{else}€{/if}
                                        </td>
                                        <td>
                                            {if !empty($p.data_inizio) && !empty($p.data_fine)}
                                                {$p.data_inizio} →<br>{$p.data_fine}
                                            {else}
                                                <span class="text-muted">Sempre valida</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </div>
            {/foreach}
        {/if}
    </div>
</div>
{/block}
