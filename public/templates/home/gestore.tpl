{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">

    {if $affiliazione_attesa|default:false}
        <div class="notification is-warning mb-3 tp-flash" role="alert">
            {icon name='clock'}
            <span>La tua <strong>affiliazione è in attesa di approvazione</strong>. Potrai creare circuiti dopo la conferma di un amministratore.</span>
        </div>
    {elseif $affiliazione_rifiutata|default:false}
        <div class="notification is-danger mb-3 tp-flash" role="alert">
            {icon name='alert'}
            <span>La tua <strong>richiesta di affiliazione è stata rifiutata</strong>. Contatta il supporto per maggiori informazioni.</span>
        </div>
    {/if}

    <section class="tp-dash-hero">
        <div class="tp-dash-hero-text">
            <p class="tp-dash-hero-eyebrow">{icon name='flag' size=13} Gestore circuiti</p>
            <h1>Benvenuto, {$user.nome|default:''}</h1>
            <p class="lead">Monitora le prenotazioni sui tuoi tracciati, l'andamento per circuito e i guadagni.</p>
        </div>
        <div class="tp-dash-hero-actions">
            <a class="button is-primary" href="{url path='/circuitiGestore'}">{icon name='flag'} I miei circuiti</a>
        </div>
    </section>

    <div class="grid grid-4 mb-3">
        <div class="kpi">{icon name='check' class='kpi-icon'}
            <div class="kpi-value">{$sessioni_concluse}</div>
            <div class="kpi-label">Sessioni concluse questo mese</div>
        </div>
        <div class="kpi is-accent">{icon name='calendar' class='kpi-icon'}
            <div class="kpi-value">{$pren_attive}</div>
            <div class="kpi-label">Prenotazioni attive</div>
        </div>
        <div class="kpi is-success">{icon name='tag' class='kpi-icon'}
            <div class="kpi-value">{$promo_attive}</div>
            <div class="kpi-label">Promozioni attive</div>
        </div>
        <div class="kpi">{icon name='wallet' class='kpi-icon'}
            <div class="kpi-value">{money value=$guadagno_mese}</div>
            <div class="kpi-label">Guadagno mensile</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="tp-card-heading tp-cc-head mb-2">
            <div class="tp-cc-head-left">
                <h3 class="card-title">Prenotazioni e ricavi{if $circuito_sel} · {$circuito_sel_nome}{/if}</h3>
                {if $circuiti|@count > 1}
                    <details class="tp-cc-dd">
                        <summary class="tp-cc-dd-trigger">{icon name='flag' size=13} {if $circuito_sel}{$circuito_sel_nome}{else}Tutti i circuiti{/if} {icon name='arrow-down' size=13}</summary>
                        <div class="tp-cc-dd-menu">
                            <a class="tp-cc-dd-opt {if !$circuito_sel}is-active{/if}" href="{url path='/dashboard'}?mesi={$mesi}">Tutti i circuiti</a>
                            {foreach from=$circuiti item=c}
                                <a class="tp-cc-dd-opt {if $circuito_sel == $c.id}is-active{/if}" href="{url path='/dashboard'}?mesi={$mesi}&circuito={$c.id}">{$c.nome_circuito}</a>
                            {/foreach}
                        </div>
                    </details>
                {/if}
            </div>
            <details class="tp-cc-dd tp-cc-dd--right">
                <summary class="tp-cc-dd-trigger">{icon name='calendar' size=13} Ultimi {$mesi} mesi {icon name='arrow-down' size=13}</summary>
                <div class="tp-cc-dd-menu">
                    <a class="tp-cc-dd-opt {if $mesi == 3}is-active{/if}" href="{url path='/dashboard'}?mesi=3{if $circuito_sel}&circuito={$circuito_sel}{/if}">Ultimi 3 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 6}is-active{/if}" href="{url path='/dashboard'}?mesi=6{if $circuito_sel}&circuito={$circuito_sel}{/if}">Ultimi 6 mesi</a>
                    <a class="tp-cc-dd-opt {if $mesi == 12}is-active{/if}" href="{url path='/dashboard'}?mesi=12{if $circuito_sel}&circuito={$circuito_sel}{/if}">Ultimi 12 mesi</a>
                </div>
            </details>
        </div>
        {combo_chart data=$andamento bar_label='Prenotazioni' line_label='Ricavi' line_currency='EUR' bar_values=false axis_titles=false empty_text='Le prenotazioni confermate sui tuoi circuiti compariranno qui mese per mese.'}
    </div>
</div>
{/block}
