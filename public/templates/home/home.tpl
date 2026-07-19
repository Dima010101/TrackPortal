{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='user' size=13} Area pilota</p>
            <h1>Ciao, {$user.nome|default:''}!</h1>
            <p class="lead">Tieni d'occhio le tue sessioni in pista e le tue prenotazioni attive.</p>
        </div>
        <div class="tp-dash-hero-actions">
            <a class="button is-primary" href="{url path='/circuiti'}">{icon name='flag'}<span>Prenota sessione</span></a>
        </div>
    </section>

    {if $profilo.documenti_stato != '' && !$profilo.documenti_completi}
        <div class="notification is-warning mb-3" role="alert">
            {icon name='alert'}
            <span>Devi caricare <strong>entrambi i documenti</strong> (certificato medico e patente/licenza) per poter prenotare le sessioni. <a href="{url path='/account'}">Vai al tuo account</a>.</span>
        </div>
    {elseif $profilo.documenti_stato == 'in_attesa'}
        <div class="notification is-warning mb-3" role="alert">
            {icon name='alert'}
            <span>I tuoi <strong>documenti sono in attesa di convalida</strong> da parte dell'amministrazione: non puoi ancora prenotare le sessioni. <a href="{url path='/account'}">Vai al tuo account</a>.</span>
        </div>
    {elseif $profilo.documenti_stato == 'respinti'}
        <div class="notification is-danger mb-3" role="alert">
            {icon name='alert'}
            <span>I tuoi <strong>documenti sono stati respinti</strong>. <a href="{url path='/account'}">Caricane di nuovi validi</a> per poter prenotare le sessioni.</span>
        </div>
    {/if}

    {if $profilo.scadenza_stato == 'scaduta'}
        <div class="notification is-danger mb-3 tp-flash" role="alert">
            {icon name='alert'}
            <span>La tua <strong>licenza è scaduta</strong> il {$profilo.scadenza_label}. Aggiorna i dati dal tuo account prima di prenotare.</span>
        </div>
    {elseif $profilo.scadenza_stato == 'in_scadenza'}
        <div class="notification is-warning mb-3 tp-flash" role="alert">
            {icon name='clock'}
            <span>La tua licenza scade tra <strong>{$profilo.giorni_scadenza} giorni</strong> ({$profilo.scadenza_label}).</span>
        </div>
    {/if}

    <div class="card tp-dash-main tp-dash-main--compact">
        <div class="tp-card-heading mb-2">
            <h3 class="card-title">Prenotazioni attive</h3>
            <a class="button is-ghost is-small" href="{url path='/prenotazioni'}">Vedi tutte {icon name='arrow-right'}</a>
        </div>
        <div class="tp-dash-main-body">
        {if $prossime|@count == 0}
            <div class="empty-state">
                {icon name='calendar' size=56}
                <h3>Nessuna prenotazione attiva</h3>
                <p>Inizia esplorando i circuiti disponibili qui sotto.</p>
            </div>
        {else}
            {foreach from=$prossime item=r}
                {assign var='rid' value=$r.id|default:0}
                {assign var='rst' value=$r.stato|default:''}
                <div class="session-row">
                    <div>
                        <div class="sess-title">{$r.nome_circuito|default:''}</div>
                        <div class="sess-meta">
                            {$r.codice_identificativo|default:''} ·
                            {date_pretty sql=$r.inizio_sessione|default:''}
                            {if $r.veicolo_noleggio_id} · {icon name='car' size=12} noleggio{/if}
                        </div>
                    </div>
                    <span class="badge {$rst}">{stato_label stato=$rst}</span>
                    <span class="tp-price">{money value=$r.prezzo_importo|default:0 currency=$r.prezzo_valuta|default:'EUR'}</span>
                    <a class="button is-dark is-small" href="{url path="/prenotazioni/{$rid}"}">{icon name='eye'} Dettaglio</a>
                </div>
            {/foreach}
        {/if}
        </div>
    </div>

    <section id="circuiti" class="mt-5">
        <div class="page-header">
            <div>
                <h2 class="section-title">I più popolari</h2>
                <p class="lead">I circuiti più prenotati questo mese.</p>
            </div>
            <a class="button is-ghost is-small" href="{url path='/circuiti'}">Tutti i circuiti {icon name='arrow-right'}</a>
        </div>
        {include file='partials/circuiti_elenco.tpl' mostra_ricerca=false}
    </section>
</div>
{/block}
