{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Convalida']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Convalida</h1>
            <p class="lead">Affiliazioni di gestori e aziende e convalida dei documenti dei piloti.</p>
        </div>
    </div>

    <h2 class="section-title">Richieste in sospeso</h2>
    {if $empty_list|default:false}
        <div class="empty-state">
            {icon name='building' size=56}
            <h3>Nessuna richiesta in sospeso</h3>
            <p>Tutte le richieste di affiliazione sono state elaborate.</p>
        </div>
    {else}
        <div class="table-wrap">
            <table class="table tp-compact-actions">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Società</th>
                        <th>Referente</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$richieste item=r}
                    {assign var='rid' value=$r.id|default:0}
                    <tr>
                        <td><span class="badge in_attesa">{$r.tipo_label|default:''}</span></td>
                        <td>
                            {if ($r.nome_societa|default:'') != ''}
                                <strong>{$r.nome_societa|escape}</strong>
                                <div class="text-muted text-small">P.IVA {$r.partita_iva|default:''|escape}</div>
                            {else}
                                <span class="text-muted">Pilota · {tp_ucfirst s=$r.categoria|default:'—'}</span>
                            {/if}
                        </td>
                        <td>
                            {$r.nome|default:''} {$r.cognome|default:''}
                            <div class="text-muted text-small">{$r.email|default:''}</div>
                        </td>
                        <td class="actions">
                            <a class="button is-primary is-small"
                               href="{url path="/affiliazioni/{$rid}"}">
                                {icon name='eye'} Dettaglio
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {/if}

    <h2 class="section-title" style="margin-top:2rem;">Storico richieste elaborate</h2>
    {if $empty_storico|default:false}
        <div class="empty-state">
            {icon name='clock' size=48}
            <h3>Nessuna richiesta elaborata</h3>
            <p>Le richieste approvate o respinte compariranno qui.</p>
        </div>
    {else}
        <div class="table-wrap">
            <table class="table tp-compact-actions">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Società</th>
                        <th>Referente</th>
                        <th>Esito</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$storico item=r}
                    {assign var='rid' value=$r.id|default:0}
                    {assign var='aff' value=$r.affiliazione|default:''}
                    {assign var='badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
                    {assign var='esito' value=($aff == 'approvata') ? 'Approvata' : (($aff == 'rifiutata') ? 'Respinta' : $aff)}
                    <tr>
                        <td>{$r.tipo_label|default:''}</td>
                        <td>
                            {if ($r.nome_societa|default:'') != ''}
                                <strong>{$r.nome_societa|escape}</strong>
                                <div class="text-muted text-small">P.IVA {$r.partita_iva|default:''|escape}</div>
                            {else}
                                <span class="text-muted">Pilota · {tp_ucfirst s=$r.categoria|default:'—'}</span>
                            {/if}
                        </td>
                        <td>
                            {$r.nome|default:''} {$r.cognome|default:''}
                            <div class="text-muted text-small">{$r.email|default:''}</div>
                        </td>
                        <td><span class="badge {$badge}">{$esito}</span></td>
                        <td class="actions">
                            <a class="button is-small"
                               href="{url path="/affiliazioni/{$rid}"}">
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
