{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Prenotazioni']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Le mie prenotazioni</h1>
            <p class="lead">Filtra tra prenotazioni attive e storico. Apri il dettaglio per ricevuta e fatture.</p>
        </div>
        <a class="button is-primary" href="{url path='/circuiti'}">
            {icon name='plus'} Nuova prenotazione
        </a>
    </div>

    <form method="get" action="{url path='/prenotazioni'}" class="tp-filter-bar">
        <div class="btn-group" role="tablist">
            {capture assign='url_attive'}{prenotazione_lista_url stato='attive' q=$q|default:''}{/capture}
            {capture assign='url_storico'}{prenotazione_lista_url stato='storico' q=$q|default:''}{/capture}
            <a href="{url path=$url_attive}"  class="{if $filtro == 'attive'}is-active{/if}">Attive</a>
            <a href="{url path=$url_storico}" class="{if $filtro == 'storico'}is-active{/if}">Storico</a>
        </div>
        <input type="hidden" name="stato" value="{$filtro}">
        <input class="input tp-filter-search" type="search" name="q" value="{$q|default:''}"
               placeholder="Cerca per circuito o codice prenotazione...">
        <button class="button is-primary tp-icon-btn" type="submit" aria-label="Cerca">
            {icon name='search'}
        </button>
    </form>

    {if $rows|@count == 0}
        <div class="empty-state">
            {icon name='history' size=56}
            <h3>Nessuna prenotazione</h3>
            <p>Non hai ancora prenotazioni in questa categoria.</p>
            <a class="button is-primary mt-2" href="{url path='/dashboard'}">Sfoglia i circuiti</a>
        </div>
    {else}
        {valuta_switcher class='tp-valuta--inline'}
        <div class="table-wrap">
            <table class="table tp-booking-table tp-compact-actions">
                <colgroup>
                    <col class="tp-col-code">
                    <col class="tp-col-circuit">
                    <col class="tp-col-date">
                    <col class="tp-col-amount">
                    {if $filtro == 'storico'}<col class="tp-col-status">{/if}
                    <col class="tp-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Circuito</th>
                        <th>Data</th>
                        <th>Importo</th>
                        {if $filtro == 'storico'}<th>Stato</th>{/if}
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$rows item=r}
                    {assign var='rid' value=$r.id|default:0}
                    {assign var='cid' value=$r.circuito_id|default:0}
                    {assign var='st' value=$r.stato|default:''}
                    {assign var='val' value=$r.prezzo_valuta|default:'EUR'}
                    <tr>
                        <td><strong>{$r.codice_identificativo|default:''}</strong></td>
                        <td><a href="{url path="/circuiti/{$cid}"}">{$r.nome_circuito|default:''}</a></td>
                        <td>{date_short sql=$r.inizio_sessione|default:''}</td>
                        <td>{prezzo value=$r.prezzo_importo|default:0 currency=$val}</td>
                        {if $filtro == 'storico'}
                            {if $st == 'cancellata'}
                                <td><span class="badge annullata">Annullata</span></td>
                            {else}
                                <td><span class="badge terminata">Terminata</span></td>
                            {/if}
                        {/if}
                        <td class="actions">
                            <a class="button is-dark is-small" href="{url path="/prenotazioni/{$rid}"}">
                                {icon name='eye'} Dettaglio
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
</div>
{/block}
