{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Storico']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Storico prenotazioni</h1>
            <p class="lead">Prenotazioni sui tuoi circuiti. Scarica la fattura o
               sospendi/banna il pilota dai tuoi circuiti.</p>
        </div>
    </div>

    <form method="get" action="{url path='/prenotazioni/storico'}" class="tp-filter-bar mb-4">
        <input class="input tp-filter-search" type="search" name="q" value="{$q|default:''}"
               placeholder="Cerca per circuito, codice o prenotante...">
        <button class="button is-primary tp-icon-btn" type="submit" aria-label="Cerca">
            {icon name='search'}
        </button>
    </form>

    {if $rows|@count == 0}
        <div class="empty-state">
            {icon name='history' size=56}
            <h3>Nessuna prenotazione</h3>
            <p>Non risultano prenotazioni sui tuoi circuiti.</p>
        </div>
    {else}
        <div class="table-wrap">
            <table class="table tp-storico-actions">
                <colgroup>
                    <col style="width:12%">
                    <col style="width:16%">
                    <col style="width:19%">
                    <col style="width:12%">
                    <col style="width:11%">
                    <col style="width:10%">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Prenotante</th>
                        <th>Circuito</th>
                        <th>Periodo</th>
                        <th>Quota accesso</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$rows item=r}
                    {assign var='rid' value=$r.id|default:0}
                    {assign var='st' value=$r.stato|default:''}
                    {assign var='val' value=$r.prezzo_valuta|default:'EUR'}
                    {assign var='fid' value=$r.fattura_id|default:0}
                    <tr>
                        <td><strong>{$r.codice_identificativo|default:''}</strong></td>
                        <td>{$r.nome|default:''} {$r.cognome|default:''}</td>
                        <td>{$r.nome_circuito|default:''}</td>
                        <td>{date_short sql=$r.inizio_sessione|default:''}</td>
                        <td>{money value=$r.imponibile_accesso|default:0 currency=$val}</td>
                        <td><span class="badge {$st}">{stato_label stato=$st}</span></td>
                        <td class="actions">
                            {if $fid}
                                <a class="button is-primary is-small" href="{url path="/prenotazioni/documento/{$fid}"}">
                                    {icon name='receipt' size=14} Fattura PDF
                                </a>
                            {else}
                                <button type="button" class="button is-small" disabled
                                        title="Fattura non ancora disponibile per questa prenotazione">
                                    {icon name='receipt' size=14} Fattura n/d
                                </button>
                            {/if}
                            {if $r.gia_sanzionato|default:false}
                                <span class="tag tp-tag-sanzionato" title="Pilota già sanzionato sui tuoi circuiti">Già sanzionato</span>
                            {else}
                                <a class="button is-danger is-small"
                                   href="{url path='/sanzioniGestore'}?pilota_id={$r.pilota_id|default:0}">
                                    {icon name='lock' size=14} Sospendi/Banna
                                </a>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
</div>
{/block}
