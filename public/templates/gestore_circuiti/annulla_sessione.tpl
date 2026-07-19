{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Calendario','url'=>'/calendario'],['label'=>'Annulla sessione']]}
    {include file='partials/breadcrumb.tpl'}
    {assign var='cid' value=$sessione.circuito_id|default:0}
    {assign var='sid' value=$sessione.id|default:0}

    <div class="page-header">
        <div>
            <h1>Annulla sessione</h1>
            <p class="lead">
                {$sessione.nome_circuito|default:''|escape} —
                {date_pretty sql=$sessione.inizio|default:''} → {date_pretty sql=$sessione.fine|default:''}
            </p>
        </div>
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

    <div class="notification is-warning">
        {icon name='alert'}
        Annullando la sessione, <strong>tutte le prenotazioni attive verranno cancellate</strong>
        e ai piloti sarà riconosciuto un <strong>rimborso del 100%</strong> dell'importo pagato.
        L'operazione non è reversibile.
    </div>

    <div class="card tp-panel-narrow">
        <h3 class="card-title">Piloti prenotati</h3>
        {if $piloti|@count == 0}
            <p class="text-muted">Non ci sono prenotazioni attive per questa sessione. Verrà solo annullata.</p>
        {else}
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Pilota</th>
                            <th>Box</th>
                            <th>Importo pagato</th>
                            <th>Rimborso (100%)</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$piloti item=p}
                        {assign var='pval' value=$p.prezzo_valuta|default:'EUR'}
                        <tr>
                            <td><strong>{$p.codice_identificativo|default:''}</strong></td>
                            <td>{$p.pilota_nome|default:''} {$p.pilota_cognome|default:''}</td>
                            <td>{if !empty($p.numero_box)}n. {$p.numero_box}{else}—{/if}</td>
                            <td>{money value=$p.prezzo_importo|default:0 currency=$pval}</td>
                            <td>{money value=$p.prezzo_importo|default:0 currency=$pval}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Totale rimborsi</th>
                            <th>{money value=$rimborso_totale|default:0 currency=$rimborso_valuta|default:'EUR'}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        {/if}

        <hr class="divider">

        <form method="post" action="{url path='/calendario/annulla'}" class="form-stack is-wide">
            {csrf_field}
            <input type="hidden" name="sessione_id" value="{$sid}">
            <input type="hidden" name="settimana" value="{$settimana|default:0}">

            <div class="form-row">
                <label>Motivazione dell'annullamento <span class="text-danger">*</span></label>
                <textarea name="causa" required maxlength="255"
                          placeholder="Es. condizioni meteo avverse, manutenzione straordinaria del tracciato...">{$causa|default:''|escape}</textarea>
            </div>

            <div class="flex">
                <button class="button is-danger" type="submit">
                    {icon name='trash'} Annulla la sessione e rimborsa i piloti
                </button>
                <a class="button is-dark"
                   href="{url path="/calendario?circuito_id={$cid}&settimana={$settimana|default:0}"}">
                    Indietro
                </a>
            </div>
        </form>
    </div>
</div>
{/block}
