{extends file='layouts/page.tpl'}

{block name=body}
{assign var='cf' value=$cat_filter|default:''}
{assign var='cid' value=$circuito.id|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Veicoli in flotta']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Veicoli in flotta</h1>
            {if $circuito}
                <p class="lead">{$circuito.nome_circuito|default:''|escape} — seleziona un veicolo per i dettagli.</p>
            {else}
                <p class="lead">Elenco veicoli per circuito.</p>
            {/if}
        </div>
        {if $cid > 0}
            <div class="btn-group">
                {capture assign='qs_tutti'}{flotta_qs circuito_id=$cid cat=''}{/capture}
                {capture assign='qs_auto'}{flotta_qs circuito_id=$cid cat='auto'}{/capture}
                {capture assign='qs_moto'}{flotta_qs circuito_id=$cid cat='moto'}{/capture}
                <a href="{url path=$qs_tutti}" class="{if $cf == ''}is-active{/if}">Tutti</a>
                <a href="{url path=$qs_auto}" class="{if $cf == 'auto'}is-active{/if}">Auto</a>
                <a href="{url path=$qs_moto}" class="{if $cf == 'moto'}is-active{/if}">Moto</a>
            </div>
        {/if}
    </div>

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
        <a class="button is-dark mt-2" href="{url path='/flotta'}">Torna ai circuiti</a>
    {elseif $circuito}
        <div class="table-wrap">
            <table class="table tp-fleet-table tp-compact-actions">
                <colgroup>
                    <col class="tp-col-vehicle">
                    <col class="tp-col-plate">
                    <col class="tp-col-price">
                    <col class="tp-col-count">
                    <col class="tp-col-status">
                    <col class="tp-col-actions">
                </colgroup>
                <thead><tr>
                    <th>Veicolo</th><th>Targa</th><th>Prezzo</th><th>Pren.</th><th>Stato</th><th></th>
                </tr></thead>
                <tbody>
                {foreach from=$veicoli item=v}
                    {assign var='vid' value=$v.id|default:0}
                    {assign var='dis' value=$v.disponibile ? true : false}
                    <tr>
                        <td><strong>{$v.marca|default:''} {$v.modello|default:''}</strong>
                            <div class="text-muted text-small">
                                {tp_ucfirst s=$v.categoria|default:''} · {$v.potenza_cv|default:0}cv · cap.{$v.capienza|default:1} · {$v.anno|default:0}
                            </div>
                        </td>
                        <td><code>{$v.targa|default:''}</code></td>
                        <td>{money value=$v.prezzo|default:0}</td>
                        <td>{$v.pren_count|default:0}</td>
                        <td>
                            <span class="badge {if $dis}confermata{else}cancellata{/if}">
                                {if $dis}Disponibile{else}Non disp.{/if}
                            </span>
                        </td>
                        <td class="actions">
                            <a href="{url path="/flotta/veicolo/{$vid}"}"
                               class="button is-dark is-small">{icon name='eye'} Dettaglio</a>
                        </td>
                    </tr>
                {/foreach}
                {if $veicoli|@count == 0}
                    <tr><td colspan="6" class="text-muted text-center">Nessun veicolo su questo circuito.</td></tr>
                {/if}
                </tbody>
            </table>
        </div>
    {/if}
</div>
{/block}
