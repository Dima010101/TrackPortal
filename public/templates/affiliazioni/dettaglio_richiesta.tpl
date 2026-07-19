{extends file='layouts/page.tpl'}

{block name=body}
{assign var='r' value=$richiesta|default:[]}
{assign var='rid' value=$r.id|default:0}
{assign var='aff' value=$r.affiliazione|default:''}
{assign var='badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Convalida','url'=>'/affiliazioni'],['label'=>'Dettaglio richiesta']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Dettaglio richiesta</h1>
            <p class="lead">{$r.tipo_label|default:''} — {if ($r.nome_societa|default:'') != ''}{$r.nome_societa|escape}{else}{$r.nome|default:''|escape} {$r.cognome|default:''|escape}{/if}</p>
        </div>
        <span class="badge {$badge}">{tp_ucfirst s=$aff|replace:'_':' '}</span>
    </div>

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <div class="grid grid-2">
        <div class="card">
            {if $is_pilota|default:false}
                <h3 class="card-title">Dati pilota</h3>
                <dl class="tp-dl">
                    <dt>Categoria</dt>
                    <dd>{tp_ucfirst s=$r.categoria|default:''}</dd>
                    <dt>Licenza</dt>
                    <dd>{$r.licenza|default:'—'|escape}</dd>
                    <dt>Scadenza licenza</dt>
                    <dd>{$r.scadenza_licenza|default:'—'|escape}</dd>
                    <dt>Tipo richiedente</dt>
                    <dd>{$r.tipo_label|default:''|escape}</dd>
                    <dt>Data richiesta</dt>
                    <dd>{$r.data_creazione|default:'—'|escape}</dd>
                </dl>
            {else}
                <h3 class="card-title">Dati azienda</h3>
                <dl class="tp-dl">
                    <dt>Ragione sociale</dt>
                    <dd>{$r.nome_societa|default:''|escape}</dd>
                    <dt>Partita IVA</dt>
                    <dd>{$r.partita_iva|default:''|escape}</dd>
                    <dt>Tipo richiedente</dt>
                    <dd>{$r.tipo_label|default:''|escape}</dd>
                    <dt>Data richiesta</dt>
                    <dd>{$r.data_creazione|default:'—'|escape}</dd>
                </dl>
            {/if}
        </div>

        <div class="card">
            <h3 class="card-title">Referente</h3>
            <dl class="tp-dl">
                <dt>Nome</dt>
                <dd>{$r.nome|default:''|escape} {$r.cognome|default:''|escape}</dd>
                <dt>Email</dt>
                <dd>{$r.email|default:''|escape}</dd>
                <dt>Stato account</dt>
                <dd>{tp_ucfirst s=$r.stato_account|default:''}</dd>
            </dl>
        </div>
    </div>

    <div class="card mt-3">
        <h3 class="card-title">Documenti</h3>
        {if $is_pilota|default:false}
            {assign var='cert' value=$r.certificato_medico_path|default:''}
            {assign var='lic' value=$r.licenza_path|default:''}
            <div class="actions">
                {if $cert != ''}
                    <a class="button is-small" href="{url path="/account/{$rid}/certificato_medico"}" target="_blank" rel="noopener">
                        {icon name='receipt'} Certificato medico (PDF)
                    </a>
                {else}
                    <span class="text-muted text-small">Certificato medico non disponibile.</span>
                {/if}
                {if $lic != ''}
                    <a class="button is-small" href="{url path="/account/{$rid}/licenza"}" target="_blank" rel="noopener">
                        {icon name='receipt'} {$etichetta_licenza|default:'Licenza / patente'} (PDF)
                    </a>
                {else}
                    <span class="text-muted text-small">{$etichetta_licenza|default:'Licenza/patente'} non disponibile.</span>
                {/if}
            </div>
        {else}
            <div class="table-wrap">
                <table class="table">
                    <tbody>
                    {foreach from=$documenti item=doc}
                        <tr>
                            <td class="text-muted">{$doc.label|escape}</td>
                            <td><strong>{$doc.valore|escape}</strong></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>

    {if $in_attesa|default:false}
        <div class="grid grid-2 mt-3">
            <div class="card">
                <h3 class="card-title">{if $is_pilota|default:false}Convalida documenti{else}Approva richiesta{/if}</h3>
                <p class="text-muted text-small">{if $is_pilota|default:false}I documenti verranno convalidati: il pilota potrà prenotare le sessioni.{else}L'utenza esistente verrà abilitata con stato affiliazione approvato.{/if}</p>
                <form method="post" action="{url path="/affiliazioni/{$rid}/approva"}">
                    {csrf_field}
                    <input type="hidden" name="tipo" value="{$r.tipo|default:''|escape}">
                    <button class="button is-primary" type="submit">{icon name='check'} Conferma approvazione</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">Respingi richiesta</h3>
                <form method="post" action="{url path="/affiliazioni/{$rid}/respingi"}" class="form-stack">
                    {csrf_field}
                    <input type="hidden" name="tipo" value="{$r.tipo|default:''|escape}">
                    <div class="form-row">
                        <label for="motivo">Motivazione (facoltativa)</label>
                        <textarea id="motivo" name="motivo" placeholder="Motivo del rifiuto…">{$motivo|default:''|escape}</textarea>
                    </div>
                    <button class="button is-danger" type="submit">{icon name='x'} Conferma rifiuto</button>
                </form>
            </div>
        </div>
    {else}
        <div class="notification is-warning mt-3">
            Questa richiesta non è più in sospeso e non può essere modificata.
        </div>
    {/if}
</div>
{/block}
