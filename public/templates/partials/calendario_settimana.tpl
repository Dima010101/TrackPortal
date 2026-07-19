{*
  Griglia calendario settimanale.
  Variabili: lunedi, etichette_giorni, griglia, ore, settimana, circuito_id,
             cal_readonly (bool), cal_nav_base (path senza query)
*}
<div class="tp-cal-toolbar mb-4">
    {assign var='cal_qs' value=$cal_nav_query|default:"circuito_id={$circuito_id|default:0}&settimana="}
    <div class="tp-cal-week-nav">
        <a class="button is-dark is-small"
           href="{url path="{$cal_nav_base|default:'/calendario'}?{$cal_qs}"}{($settimana|default:0) - 1}">
            {icon name='arrow-left'} Settimana precedente
        </a>
        <span class="tp-cal-week-label">
            Settimana dal {date_short sql="{$lunedi|default:''} 00:00:00"}
        </span>
        <a class="button is-dark is-small"
           href="{url path="{$cal_nav_base|default:'/calendario'}?{$cal_qs}"}{($settimana|default:0) + 1}">
            Settimana successiva {icon name='arrow-right'}
        </a>
    </div>
</div>

<div class="tp-cal-legend mb-3">
    <span class="tp-cal-legend-item tp-cal-slot--libero">Libero</span>
    <span class="tp-cal-legend-item tp-cal-slot--amatoriale">Amatoriale</span>
    <span class="tp-cal-legend-item tp-cal-slot--professionistica">Professionistica</span>
    <span class="tp-cal-legend-item tp-cal-slot--privata">Privata</span>
    <span class="tp-cal-legend-item tp-cal-slot--prenotato">Prenotato</span>
</div>

<div class="table-wrap tp-cal-wrap">
    <table class="table tp-cal-table">
        <thead>
            <tr>
                <th class="tp-cal-ora-col">Ora</th>
                {foreach from=$etichette_giorni item=g}
                    <th>{$g.label|escape}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody>
            {foreach from=$ore item=ora}
                <tr>
                    <td class="tp-cal-ora-col">{$ora|escape}</td>
                    {foreach from=$etichette_giorni item=g}
                        {assign var='cell' value=$griglia[$g.data][$ora]|default:[]}
                        {if $cell.continuazione|default:false}
                            {* Cella assorbita dal rowspan della cella iniziale della sessione. *}
                        {else}
                        {assign var='stato' value=$cell.stato|default:'libero'}
                        {assign var='sid' value=$cell.sessione.id|default:0}
                        {assign var='postiLiberi' value=$cell.sessione.posti_liberi|default:0}
                        {assign var='rowspan' value=$cell.rowspan|default:1}
                        {assign var='isBookable' value=($stato == 'amatoriale' || $stato == 'professionistica')}
                        {assign var='isCat' value=($isBookable || $stato == 'privata')}
                        {assign var='catLabel' value=($stato == 'amatoriale') ? 'Amatoriale' : (($stato == 'professionistica') ? 'Professionistica' : 'Privata')}
                        {* Il link di prenotazione è riservato ai piloti (cal_prenotabile) e agli
                           utenti non loggati (cal_richiede_login -> redirect al login). Gestori,
                           noleggiatori e admin vedono solo un badge statico: non devono poter
                           navigare verso /prenotazione/{id} (che li respingerebbe con 403). *}
                        {assign var='prenotabile' value=($cal_readonly|default:false && $isBookable && $sid > 0 && $postiLiberi > 0 && ($cal_prenotabile|default:false || $cal_richiede_login|default:false))}
                        <td class="tp-cal-cell tp-cal-slot--{$stato|escape}{if $rowspan > 1} tp-cal-cell--span{/if}{if $cell.mia_prenotazione|default:false} tp-cal-slot--mia-prenotazione{/if}{if $prenotabile} tp-cal-cell--clickable{/if}"{if $rowspan > 1} rowspan="{$rowspan}"{/if}>
                            {if $prenotabile}
                                <a class="tp-cal-link"
                                   href="{url path="/prenotazione/{$sid}"}"
                                   title="{if $cell.mia_prenotazione|default:false}La tua prenotazione{elseif $cal_prenotabile|default:false}Prenota questa sessione {$catLabel} ({$postiLiberi} posti liberi){elseif $cal_richiede_login|default:false}Accedi per prenotare{else}Prenota questa sessione (account pilota){/if}">
                                    <span class="tp-cal-badge is-open"><strong>{$catLabel}</strong><br>{$postiLiberi} liberi</span>
                                </a>
                            {elseif $stato == 'prenotato'}
                                <span class="tp-cal-badge" title="Slot prenotato">Pren.</span>
                            {elseif $isCat}
                                {if $cal_readonly|default:false}
                                    {if $isBookable}
                                        <span class="tp-cal-badge is-open" title="{if $postiLiberi <= 0}Sessione al completo{elseif $cell.mia_prenotazione|default:false}La tua prenotazione{else}Sessione {$catLabel}{/if}"><strong>{$catLabel}</strong><br>{if $postiLiberi <= 0}Completo{else}{$postiLiberi} liberi{/if}</span>
                                    {else}
                                        <span class="tp-cal-badge is-closed" title="Sessione privata">{icon name='lock'} Privata</span>
                                    {/if}
                                {else}
                                    <a class="tp-cal-link"
                                       href="{url path="/calendario/slot/{$g.data}/{$ora}?circuito_id={$circuito_id|default:0}&settimana={$settimana|default:0}"}"
                                       title="Modifica sessione {$catLabel}">
                                        {if $isBookable}
                                            <span class="tp-cal-badge is-open"><strong>{$catLabel}</strong><br>{$cell.sessione.posti_liberi|default:1} liberi</span>
                                        {else}
                                            <span class="tp-cal-badge is-closed">{icon name='lock'} Privata</span>
                                        {/if}
                                    </a>
                                {/if}
                            {else}
                                {if $cal_readonly|default:false}
                                    <span class="tp-cal-link tp-cal-link--empty tp-cal-readonly-empty" title="Libero">—</span>
                                {else}
                                    <a class="tp-cal-link tp-cal-link--empty"
                                       href="{url path="/calendario/slot/{$g.data}/{$ora}?circuito_id={$circuito_id|default:0}&settimana={$settimana|default:0}"}"
                                       title="Crea sessione">+</a>
                                {/if}
                            {/if}
                        </td>
                        {/if}
                    {/foreach}
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
