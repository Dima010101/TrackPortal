{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='car' size=13} Azienda di noleggio</p>
            <h1>Ciao, {$user.nome|default:''}</h1>
            <p class="lead">Gestisci la flotta, controlla i veicoli più richiesti e i noleggi in arrivo.</p>
        </div>
        <div class="tp-dash-hero-actions">
            <a class="button is-primary" href="{url path='/flotta'}">{icon name='car'} Gestisci flotta</a>
            <a class="button is-dark" href="{url path='/promozioni'}">{icon name='tag'} Promozioni</a>
        </div>
    </section>

    <div class="grid grid-4 mb-3">
        <div class="kpi">{icon name='car' class='kpi-icon'}
            <div class="kpi-value">{$tot_veicoli}</div>
            <div class="kpi-label">Veicoli in flotta</div>
        </div>
        <div class="kpi is-success">{icon name='check' class='kpi-icon'}
            <div class="kpi-value">{$tot_dispo}</div>
            <div class="kpi-label">Disponibili ora</div>
            {if $tot_veicoli > 0}
                <div class="tp-gauge-line">
                    <span class="tp-bar-chart">
                        <span class="tp-bar-fill" style="width: {($tot_dispo/$tot_veicoli*100)|round}%"></span>
                    </span>
                    <span class="tp-gauge-value">{($tot_dispo/$tot_veicoli*100)|round}%</span>
                </div>
            {/if}
        </div>
        <div class="kpi is-accent">{icon name='history' class='kpi-icon'}
            <div class="kpi-value">{$tot_pren}</div>
            <div class="kpi-label">Noleggi attivi</div>
        </div>
        <div class="kpi">{icon name='wallet' class='kpi-icon'}
            <div class="kpi-value">{money value=$guadagno}</div>
            <div class="kpi-label">Guadagno cumulato</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="tp-card-heading tp-cc-head mb-2">
            <h3 class="card-title">Prenotazione e ricavi dei veicoli</h3>
            <details class="tp-cc-dd tp-cc-dd--right">
                <summary class="tp-cc-dd-trigger">{icon name='calendar' size=13} Ultimi {$mesi} mesi {icon name='arrow-down' size=13}</summary>
                <div class="tp-cc-dd-menu">
                    <a class="tp-cc-dd-opt {if $mesi == 3}is-active{/if}" href="{url path='/dashboard'}?mesi=3">Ultimi 3 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 6}is-active{/if}" href="{url path='/dashboard'}?mesi=6">Ultimi 6 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 12}is-active{/if}" href="{url path='/dashboard'}?mesi=12">Ultimi 12 mesi</a>
                </div>
            </details>
        </div>
        {combo_chart data=$andamento bar_label='Prenotazioni' line_label='Ricavi' line_currency='EUR' bar_values=false axis_titles=false empty_text='I noleggi confermati dei tuoi veicoli compariranno qui mese per mese.'}
    </div>

    <div class="tp-dash-cols">
        <div class="card tp-dash-main">
            <div class="tp-card-heading mb-2">
                <h3 class="card-title">Prossimi noleggi</h3>
                <a class="button is-ghost is-small" href="{url path='/prenotazioni/storico'}">Storico {icon name='arrow-right'}</a>
            </div>
            <div class="tp-dash-main-body">
            {if $prossimi|@count == 0}
                <div class="empty-state">
                    {icon name='car' size=56}
                    <h3>Nessun noleggio in arrivo</h3>
                    <p>Assicurati che i veicoli siano disponibili sui circuiti giusti.</p>
                    <a class="button is-primary mt-2" href="{url path='/flotta'}">Gestisci flotta</a>
                </div>
            {else}
                {foreach from=$prossimi item=r}
                    <div class="tp-feed-row">
                        <div class="tp-feed-title">{$r.v_marca|default:''} {$r.v_modello|default:''} <span class="text-muted text-small">· {$r.v_targa|default:''}</span></div>
                        <div class="tp-feed-meta">
                            {icon name='flag' size=12} {$r.nome_circuito|default:''}
                            · {date_pretty sql=$r.inizio_sessione|default:''}
                            · {$r.codice_identificativo|default:''}
                        </div>
                        <div class="tp-feed-aside">
                            <span class="tp-feed-amount">{money value=$r.imponibile_noleggio|default:0 currency=$r.prezzo_valuta|default:'EUR'}</span>
                        </div>
                    </div>
                {/foreach}
            {/if}
            </div>
        </div>

        <div class="tp-dash-side">
            <div class="card">
                <h3 class="card-title">Veicoli più richiesti</h3>
                {if $top_veicoli|@count == 0}
                    <p class="text-muted text-small mb-0">Qui vedrai i veicoli con più prenotazioni.</p>
                {else}
                    <div class="tp-kv-list">
                        {foreach from=$top_veicoli item=v}
                            <div class="tp-kv-row">
                                <span class="tp-kv-key">
                                    {icon name='trophy' size=15}
                                    {$v.marca|default:''} {$v.modello|default:''}
                                    <span class="badge {if $v.disponibile}confermata{else}cancellata{/if}">{if $v.disponibile}Disponibile{else}Occupato{/if}</span>
                                </span>
                                <span class="tp-kv-val">{$v.prenotazioni|default:0} <span class="text-muted text-small">noleggi</span></span>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>

            <div class="card">
                <h3 class="card-title">Flotta per circuito</h3>
                {if $per_circuito|@count == 0}
                    <p class="text-muted text-small mb-0">Distribuisci i veicoli sui circuiti per riceverne prenotazioni.</p>
                {else}
                    <div class="tp-kv-list">
                        {foreach from=$per_circuito item=c}
                            <div class="tp-kv-row">
                                <span class="tp-kv-key">{icon name='flag' size=15} {$c.nome_circuito|default:''}</span>
                                <span class="tp-kv-val">{$c.veicoli_disponibili|default:0}/{$c.veicoli_count|default:0} <span class="text-muted text-small">disp.</span></span>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>
{/block}
