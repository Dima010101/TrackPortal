{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    {assign var='loc' value=$circuito.localita|default:''|trim}
    {assign var='bc_nome' value=$circuito.nome_circuito|default:''}
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>$bc_nome]]}
    {include file='partials/breadcrumb.tpl'}

    <section class="hero">
        <h1>{$circuito.nome_circuito|default:''}</h1>
        <p class="lead tp-lead-inline">
            {icon name='home'}
            {if $loc != ''}{$loc}{else}{$circuito.indirizzo|default:''}{/if}
        </p>
        <div class="flex mt-2">
            <span class="badge confermata">{icon name='flag' size=12} {tp_ucfirst s=$circuito.tipologia_veicoli|default:''}</span>
            <span class="badge">{$circuito.numero_box|default:0} box</span>
            {if !empty($circuito.lunghezza_km)}
                <span class="badge">{$circuito.lunghezza_km} km</span>
            {/if}
            {if !empty($circuito.gestore_nome)}
                <span class="text-muted">Gestito da: <strong>{$circuito.gestore_nome}</strong></span>
            {/if}
        </div>
        {if $user && $user.ruolo == 'gestore_circuiti' && $user.id == $circuito.gestore_id}
            <div class="flex mt-3">
                <a class="button is-primary" href="{url path="/circuitiGestore/`$circuito.id`/modifica"}">
                    {icon name='edit'} Modifica circuito
                </a>
                <span class="text-muted text-small">
                    Anteprima pubblica del tuo circuito: da qui puoi modificarne i dati e le foto.
                </span>
            </div>
        {/if}
    </section>

    <div class="detail-grid tp-cal-row mb-3">
        {* Colonna sinistra: calendario (fino alle 19), poi recapiti, poi veicoli disponibili. *}
        <div class="tp-cal-stack">
            <section class="card tp-cal-card">
                <div class="tp-cal-card-head">
                    <h3 class="card-title">{icon name='calendar'} Calendario disponibilità</h3>
                </div>
                {include file='partials/calendario_settimana.tpl'
                    lunedi=$calendario.lunedi
                    etichette_giorni=$calendario.etichette_giorni
                    griglia=$calendario.griglia
                    ore=$calendario.ore
                    settimana=$calendario.settimana
                    circuito_id=$circuito.id|default:0
                    cal_readonly=true
                    cal_nav_base=$calendario.cal_nav_base
                    cal_nav_query=$calendario.cal_nav_query
                    cal_prenotabile=$calendario.cal_prenotabile|default:false
                    cal_richiede_login=$calendario.cal_richiede_login|default:false
                }
            </section>

            {if !empty($circuito.telefono) || !empty($circuito.email) || !empty($circuito.sito_web)}
            <div class="card">
                <h3 class="card-title">Recapiti</h3>
                <dl class="tp-dl">
                    {if !empty($circuito.telefono)}
                        <dt>Telefono</dt>
                        <dd><a href="tel:{$circuito.telefono|escape}">{$circuito.telefono|escape}</a></dd>
                    {/if}
                    {if !empty($circuito.email)}
                        <dt>Email</dt>
                        <dd><a href="mailto:{$circuito.email|escape}">{$circuito.email|escape}</a></dd>
                    {/if}
                    {if !empty($circuito.sito_web)}
                        <dt>Sito web</dt>
                        <dd><a href="{$circuito.sito_web|escape}" target="_blank" rel="noopener">{$circuito.sito_web|escape}</a></dd>
                    {/if}
                </dl>
            </div>
            {/if}

            <div class="card">
                <h3 class="card-title">Veicoli a noleggio disponibili</h3>
                <p class="text-muted text-small mb-0">
                    La tariffa di accesso alla pista è indicata su ogni sessione del calendario.
                    Per prenotare, seleziona una sessione della tua categoria nel calendario sopra.
                </p>

                {if $veicoli_disp|@count == 0}
                    <div class="empty-state mt-3">
                        {icon name='car' size=56}
                        <h3>Nessun veicolo a noleggio</h3>
                        <p>Puoi comunque prenotare con il tuo veicolo.</p>
                    </div>
                {else}
                    <div class="grid grid-2 vehicle-grid">
                        {foreach from=$veicoli_disp item=v}
                            <div class="vehicle-card">
                                <div class="vehicle-card-head">
                                    <strong class="vehicle-card-title">
                                        {$v.marca|default:''|escape} {$v.modello|default:''|escape}
                                    </strong>
                                    <span class="tp-price">
                                        {money value=$v.prezzo|default:0}
                                    </span>
                                </div>
                                <div class="vehicle-card-meta text-muted text-small">
                                    <span class="badge confermata">{tp_ucfirst s=$v.categoria|default:''}</span>
                                    {if $v.potenza_cv|default:0 > 0}
                                        <span>{$v.potenza_cv|default:0} cv</span>
                                    {/if}
                                    <span>Cap. {$v.capienza|default:1}</span>
                                </div>
                                <div class="vehicle-card-meta text-muted text-small">
                                    {icon name='car' size=12}
                                    <code>{$v.targa|default:''|escape}</code>
                                    · {$v.nome_societa|default:''|escape}
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>
        </div>

        {* Colonna destra: descrizione + tutte le foto + veicoli popolari (invariate). *}
        <aside class="tp-detail-side">
            <div class="card">
                <h3 class="card-title">Descrizione</h3>
                <p class="mb-0">
                    {assign var='desc' value=$circuito.descrizione|default:''}
                    {if $desc != ''}
                        {$desc|tp_nl2br nofilter}
                    {else}
                        Nessuna descrizione disponibile.
                    {/if}
                </p>
            </div>

            {if !empty($circuito.foto)}
                {foreach from=$circuito.foto item=foto}
                    <figure class="card tp-foto-card">
                        <img src="{url path=$foto.path_file}"
                             alt="{$foto.didascalia|default:$circuito.nome_circuito|escape}" loading="lazy">
                        {if !empty($foto.didascalia)}
                            <figcaption class="text-muted text-small">{$foto.didascalia|escape}</figcaption>
                        {/if}
                    </figure>
                {/foreach}
            {/if}

            <aside class="card tp-popular-aside">
                <h3 class="card-title">Veicoli a noleggio popolari</h3>
                {if $top_veicoli|@count == 0}
                    <p class="text-muted mb-0">Nessun veicolo a noleggio per questo circuito.</p>
                {else}
                    {foreach from=$top_veicoli item=v}
                        <div class="tp-list-row">
                            <strong>{$v.marca|default:''} {$v.modello|default:''}</strong>
                            <div class="text-muted text-small mt-2">
                                {icon name='car' size=12} {tp_ucfirst s=$v.categoria|default:''}
                                · {money value=$v.prezzo|default:0}
                                · {$v.prenotazioni|default:0} prenotazioni
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </aside>
        </aside>
    </div>
</div>
{/block}
