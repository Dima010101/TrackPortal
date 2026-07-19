{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='settings' size=13} Amministrazione</p>
            <h1>Pannello di controllo</h1>
            <p class="lead">Stato generale della piattaforma TrackPortal: utenti, attività economica e richieste da evadere.</p>
        </div>
        <div class="tp-dash-hero-actions">
            {if $pending_aff > 0}
                <a class="button is-primary" href="{url path='/affiliazioni'}">{icon name='alert'} {$pending_aff} richieste in attesa</a>
            {else}
                <a class="button is-primary" href="{url path='/commissioni'}">{icon name='gauge'} Parametri piattaforma</a>
            {/if}
            <a class="button is-dark" href="{url path='/fatture'}">{icon name='receipt'} Fatturazione</a>
        </div>
    </section>

    <div class="grid grid-4 mb-3">
        <div class="kpi">{icon name='users' class='kpi-icon'}
            <div class="kpi-value">{$tot_utenti}</div>
            <div class="kpi-label">Utenti registrati</div>
        </div>
        <div class="kpi is-accent">{icon name='flag' class='kpi-icon'}
            <div class="kpi-value">{$tot_circuiti}</div>
            <div class="kpi-label">Circuiti</div>
        </div>
        <div class="kpi is-success">{icon name='history' class='kpi-icon'}
            <div class="kpi-value">{$tot_pren}</div>
            <div class="kpi-label">Prenotazioni totali</div>
        </div>
        <div class="kpi">{icon name='trending' class='kpi-icon'}
            <div class="kpi-value">{money value=$commissioni}</div>
            <div class="kpi-label">Commissioni stimate</div>
            <div class="kpi-trend">su {money value=$ricavi} di ricavi · {$pct_comm}%</div>
        </div>
    </div>

    <h2 class="section-title mt-4 mb-2">Assicurazioni</h2>
    <div class="grid grid-4 mb-3">
        <div class="kpi">{icon name='lock' class='kpi-icon'}
            <div class="kpi-value">{$tot_assicur}</div>
            <div class="kpi-label">Assicurazioni totali</div>
        </div>
        <div class="kpi is-success">{icon name='wallet' class='kpi-icon'}
            <div class="kpi-value">{money value=$ricavi_assicur}</div>
            <div class="kpi-label">Ricavi da assicurazioni</div>
        </div>
        <div class="kpi is-accent">{icon name='gauge' class='kpi-icon'}
            <div class="kpi-value">{$pct_assicur}%</div>
            <div class="kpi-label">Prenotazioni assicurate</div>
        </div>
        <div class="kpi">{icon name='tag' class='kpi-icon'}
            <div class="kpi-value">{money value=$prezzo_ass}</div>
            <div class="kpi-label">Prezzo attuale assicurazione</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="tp-card-heading tp-cc-head mb-2">
            <h3 class="card-title">Assicurazioni stipulate e ricavi nel tempo</h3>
            <details class="tp-cc-dd tp-cc-dd--right">
                <summary class="tp-cc-dd-trigger">{icon name='calendar' size=13} Ultimi {$mesi} mesi {icon name='arrow-down' size=13}</summary>
                <div class="tp-cc-dd-menu">
                    <a class="tp-cc-dd-opt {if $mesi == 3}is-active{/if}" href="{url path='/dashboard'}?mesi=3">Ultimi 3 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 6}is-active{/if}" href="{url path='/dashboard'}?mesi=6">Ultimi 6 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 12}is-active{/if}" href="{url path='/dashboard'}?mesi=12">Ultimi 12 mesi</a>
                </div>
            </details>
        </div>
        {combo_chart data=$andamento_assicur bar_label='Assicurazioni stipulate' line_label='Ricavi da assicurazioni' line_currency='EUR' bar_values=false axis_titles=false empty_text='Le assicurazioni stipulate sulle prenotazioni compariranno qui mese per mese.'}
        <p class="text-muted text-small mb-0 mt-2">{icon name='info' size=13} I ricavi sono stimati moltiplicando le assicurazioni stipulate per il prezzo corrente ({money value=$prezzo_ass}).</p>
    </div>

    <div class="tp-dash-cols">
        <div class="card tp-dash-main">
            <div class="tp-card-heading mb-2">
                <h3 class="card-title">Ultime prenotazioni</h3>
                <a class="button is-ghost is-small" href="{url path='/fatture'}">Fatturazione {icon name='arrow-right'}</a>
            </div>
            <div class="tp-dash-main-body">
            {if $ultime_pren|@count == 0}
                <div class="empty-state">
                    {icon name='receipt' size=56}
                    <h3>Nessuna prenotazione</h3>
                    <p>Le prenotazioni più recenti compariranno qui.</p>
                </div>
            {else}
                {foreach from=$ultime_pren item=r}
                    <div class="tp-feed-row">
                        <div class="tp-feed-title">{$r.nome|default:''} {$r.cognome|default:''}</div>
                        <div class="tp-feed-meta">
                            {icon name='flag' size=12} {$r.nome_circuito|default:''}
                            · {date_pretty sql=$r.data_inserimento|default:''}
                            · {$r.codice_identificativo|default:''}
                        </div>
                        <div class="tp-feed-aside">
                            <span class="tp-feed-amount">{money value=$r.prezzo_importo|default:0 currency=$r.prezzo_valuta|default:'EUR'}</span>
                        </div>
                    </div>
                {/foreach}
            {/if}
            </div>
        </div>

        <div class="tp-dash-side">
            <div class="card mb-3">
                <h3 class="card-title">Convalida</h3>
                {if $pending_aff > 0}
                    <div class="tp-kv-list">
                        <div class="tp-kv-row">
                            <span class="tp-kv-key">{icon name='flag' size=15} Gestori circuiti</span>
                            <span class="tp-kv-val">{$pending_circ}</span>
                        </div>
                        <div class="tp-kv-row">
                            <span class="tp-kv-key">{icon name='car' size=15} Aziende noleggio</span>
                            <span class="tp-kv-val">{$pending_nol}</span>
                        </div>
                        <div class="tp-kv-row">
                            <span class="tp-kv-key">{icon name='user' size=15} Piloti (documenti)</span>
                            <span class="tp-kv-val">{$pending_piloti|default:0}</span>
                        </div>
                    </div>
                    <div class="tp-card-link-row">
                        <a class="button is-primary is-small" href="{url path='/affiliazioni'}">{icon name='building'} Esamina richieste</a>
                    </div>
                {else}
                    <p class="text-muted text-small mb-0">{icon name='check' size=14} Nessuna richiesta in attesa. Tutto in regola.</p>
                {/if}
            </div>

            <div class="card mb-3">
                <h3 class="card-title">Composizione utenti</h3>
                {donut_chart data=$composizione_utenti center_label='utenti' unit='utenti' empty_text='Nessun utente registrato.'}
            </div>

            <div class="card">
                <h3 class="card-title">Parametri economici</h3>
                <div class="tp-kv-list">
                    <div class="tp-kv-row">
                        <span class="tp-kv-key">{icon name='lock' size=15} Prezzo assicurazione</span>
                        <span class="tp-kv-val">{money value=$prezzo_ass}</span>
                    </div>
                    <div class="tp-kv-row">
                        <span class="tp-kv-key">{icon name='gauge' size=15} Commissione</span>
                        <span class="tp-kv-val">{$pct_comm}%</span>
                    </div>
                    <div class="tp-kv-row">
                        <span class="tp-kv-key">{icon name='check' size=15} Con assicurazione</span>
                        <span class="tp-kv-val">{$tot_assicur}</span>
                    </div>
                </div>
                <div class="tp-card-link-row">
                    <a class="button is-ghost is-small" href="{url path='/commissioni'}">{icon name='settings'} Modifica parametri</a>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
