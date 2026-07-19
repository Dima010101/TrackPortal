{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Calendario','url'=>'/calendario'],['label'=>'Scheda sessione']]}
    {include file='partials/breadcrumb.tpl'}
    {if $circuito}
        {assign var='cid' value=$circuito.id|default:0}
    {/if}

    <div class="page-header">
        <div>
            <h1>Scheda sessione</h1>
            <p class="lead">
                {if $circuito}
                    {$circuito.nome_circuito|default:''|escape} —
                {/if}
                {$data|default:''|escape} alle {$ora|default:''|escape}
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

    {assign var='sid' value=$sessione_id|default:0}
    <div class="tp-session-split">
    <div class="card tp-panel-narrow tp-session-split__form">
        {if $bloccato|default:false}
            <p class="text-muted">Non è possibile creare una sessione in questo slot perché risulta già prenotato da un pilota.</p>
            {if $circuito}
                <a class="button is-dark mt-3"
                   href="{url path="/calendario?circuito_id={$cid}&settimana={$settimana|default:0}"}">
                    Torna al calendario
                </a>
            {/if}
        {else}
            <form method="post" action="{url path='/calendario/sessione'}" class="form-stack is-wide">
                {csrf_field}
                <input type="hidden" name="circuito_id" value="{$circuito.id|default:0}">
                <input type="hidden" name="data" value="{$data|default:''|escape}">
                <input type="hidden" name="ora" value="{$ora|default:''|escape}">
                <input type="hidden" name="sessione_id" value="{$sessione_id|default:0}">
                <input type="hidden" name="settimana" value="{$settimana|default:0}">

                <div class="form-row">
                    <label>Circuito</label>
                    <input type="text" value="{$circuito.nome_circuito|default:'—'|escape}" readonly disabled>
                </div>
                <div class="form-row">
                    <label>Data</label>
                    <input type="text" value="{$data|default:''|escape}" readonly disabled>
                </div>
                <div class="form-row">
                    <label>Ora inizio</label>
                    <input type="text" value="{$ora|default:''|escape}" readonly disabled>
                </div>
                <div class="form-row">
                    <label>Durata (ore)</label>
                    <select name="durata">
                        <option value="1" {if $durata|default:1 == 1}selected{/if}>1 ora</option>
                        <option value="2" {if $durata|default:1 == 2}selected{/if}>2 ore</option>
                        <option value="3" {if $durata|default:1 == 3}selected{/if}>3 ore</option>
                        <option value="4" {if $durata|default:1 == 4}selected{/if}>4 ore</option>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-row">
                        <label for="tariffa_accesso">Tariffa accesso pista</label>
                        <input type="number" id="tariffa_accesso" name="tariffa_accesso" step="0.01" min="0"
                               inputmode="decimal" required placeholder="es. 200.00"
                               value="{$tariffa_accesso|default:''|escape}">
                    </div>
                    <div class="form-row">
                        <label for="tariffa_valuta">Valuta</label>
                        {assign var='tvSel' value=$tariffa_valuta|default:'EUR'}
                        <select id="tariffa_valuta" name="tariffa_valuta">
                            {foreach from=$valute|default:['EUR'] item=vcode}
                                <option value="{$vcode}" {if $tvSel == $vcode}selected{/if}>{$vcode}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <p class="text-muted text-small">
                    La tariffa è riferita a QUESTA sessione. Se scegli una valuta diversa
                    dall'euro, l'importo viene convertito con i tassi correnti e salvato sempre in EUR.
                </p>
                <div class="form-row">
                    <label>Posti massimi</label>
                    <input type="number" name="posti_max" min="1" max="99" step="1" inputmode="numeric"
                           value="{$posti_max|default:1}" required>
                </div>
                <div class="form-row">
                    <label>Posti per box</label>
                    <input type="number" name="posti_per_box" min="1" max="99" step="1" inputmode="numeric"
                           value="{$posti_per_box|default:1}" required>
                    <p class="text-muted text-small">
                        Il circuito ha <strong>{$circuito.numero_box|default:0}</strong> box.
                        Capacità massima: <strong>{$circuito.numero_box|default:0} × posti per box</strong>.
                        I piloti vengono assegnati automaticamente al box con meno occupanti.
                    </p>
                </div>
                <div class="form-row">
                    <label>Categoria sessione</label>
                    {assign var='catSel' value=$stato|default:'privata'}
                    <select name="stato">
                        <option value="amatoriale" {if $catSel == 'amatoriale'}selected{/if}>Amatoriale</option>
                        <option value="professionistica" {if $catSel == 'professionistica'}selected{/if}>Professionistica</option>
                        <option value="privata" {if $catSel == 'privata'}selected{/if}>Privata (nessuna prenotazione)</option>
                    </select>
                    <p class="text-muted text-small">
                        La prenotazione è riservata ai piloti della categoria scelta.
                        «Privata» non ammette prenotazioni da parte di alcun pilota.
                    </p>
                </div>
                <div class="form-row">
                    <label>Note (opzionale)</label>
                    <textarea name="note" placeholder="Es. sessione riservata a piloti esperti">{$note|default:''|escape}</textarea>
                </div>

                <div class="flex">
                    <button class="button is-primary" type="submit">
                        {icon name='check'} Salva sessione
                    </button>
                    {if $circuito}
                        <a class="button is-dark"
                           href="{url path="/calendario?circuito_id={$cid}&settimana={$settimana|default:0}"}">
                            Indietro
                        </a>
                    {/if}
                </div>
            </form>

            {if $sid > 0}
                <hr class="divider">
                <div class="cancel-section">
                    <div class="cancel-section-icon">{icon name='alert' size=22}</div>
                    <div class="cancel-section-body">
                        <h3 class="cancel-section-title">Annulla sessione</h3>
                        <p class="text-muted mb-0">Cancella la sessione indicando una motivazione. Tutti i piloti prenotati riceveranno il <strong>rimborso del 100%</strong>.</p>
                    </div>
                    <a href="{url path="/calendario/{$sessione_id}/annulla?settimana={$settimana|default:0}"}"
                       class="button is-danger cancel-section-action">
                        {icon name='trash'} Annulla sessione
                    </a>
                </div>
            {/if}
        {/if}
    </div>

    {if $sid > 0 && !$bloccato|default:false}
        <div class="card tp-session-split__bookings">
            <div class="flex-between mb-4">
                <h3 class="card-title mb-0">Piloti prenotati</h3>
                <span class="text-muted text-small">
                    {$confermate|default:0} / {$posti_max|default:0} posti ·
                    incasso {money value=$incasso|default:0 currency=$incasso_valuta|default:'EUR'}
                </span>
            </div>

            {if $empty_list|default:true}
                <div class="empty-state">
                    {icon name='users' size=48}
                    <h3>Nessuna prenotazione</h3>
                    {if !$prenotabile|default:false}
                        <p>Questa è una sessione privata: non ammette prenotazioni da parte dei piloti.</p>
                    {else}
                        <p>Non risultano ancora prenotazioni per questa sessione.</p>
                    {/if}
                </div>
            {else}
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Codice</th>
                                <th>Pilota</th>
                                <th>Box</th>
                                <th>Veicolo</th>
                                <th>Assicurazione</th>
                                <th>Quota accesso</th>
                                <th>Stato</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach from=$prenotazioni item=p}
                            {assign var='pid' value=$p.id|default:0}
                            {assign var='st' value=$p.stato|default:''}
                            {assign var='val' value=$p.prezzo_valuta|default:'EUR'}
                            <tr>
                                <td><strong>{$p.codice_identificativo|default:''}</strong></td>
                                <td>
                                    {$p.pilota_nome|default:''} {$p.pilota_cognome|default:''}
                                    {if !empty($p.pilota_email)}
                                        <br><span class="text-muted text-small">{$p.pilota_email}</span>
                                    {/if}
                                </td>
                                <td>{if !empty($p.numero_box)}n. {$p.numero_box}{else}—{/if}</td>
                                <td>
                                    {if !empty($p.veicolo_noleggio_id)}
                                        Noleggio: {$p.v_marca|default:''} {$p.v_modello|default:''}
                                        {if !empty($p.v_targa)}({$p.v_targa}){/if}
                                    {elseif !empty($p.targa_veicolo)}
                                        Proprio · {$p.targa_veicolo}
                                    {else}
                                        —
                                    {/if}
                                </td>
                                <td>{if $p.assicurazione}Sì{else}No{/if}</td>
                                <td>{money value=$p.imponibile_accesso|default:0 currency=$val}</td>
                                <td><span class="badge {$st}">{stato_label stato=$st}</span></td>
                                <td class="actions">
                                    <a class="button is-primary is-small" href="{url path="/prenotazioni/{$pid}/fattura"}">
                                        {icon name='receipt'} Ricevuta PDF
                                    </a>
                                    {if $p.gia_sanzionato|default:false}
                                        <span class="tag tp-tag-sanzionato" title="Pilota già sanzionato sui tuoi circuiti">Già sanzionato</span>
                                    {else}
                                        <a class="button is-danger is-small"
                                           href="{url path='/sanzioniGestore'}?pilota_id={$p.pilota_id|default:0}">
                                            {icon name='lock'} Sanziona
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
    {/if}
    </div>
</div>
{/block}
