{*
    Gestione piloti — ban / sospensioni. UI condivisa tra gestore circuiti e
    azienda di noleggio.
    Parametri:
      - base_path : prefisso rotte ('/sanzioniGestore' | '/sanzioniNoleggio')
      - ambito    : 'circuiti' | 'noleggio'  (seleziona la copy)
    Dati (assegnati dalla view): sanzioni, piloti, can_add, empty_list, errors, form, oggi
*}
{assign var='base_path' value=$base_path|default:'/sanzioniGestore'}
{assign var='ambito' value=$ambito|default:'circuiti'}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Sanzioni']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Sanzioni</h1>
            <p class="lead">
                {if $ambito == 'noleggio'}
                    Banna o sospendi un pilota dai <strong>tuoi</strong> veicoli a noleggio. Il provvedimento
                    vale solo per i veicoli della tua azienda: il pilota resta attivo su TrackPortal e può
                    noleggiare dalle altre aziende.
                {else}
                    Banna o sospendi un pilota dai <strong>tuoi</strong> circuiti. Il provvedimento vale solo
                    per il gruppo di circuiti che gestisci: il pilota resta attivo su TrackPortal e sui
                    circuiti di altri gestori.
                {/if}
            </p>
        </div>
    </div>

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <div class="grid grid-2 tp-sanzioni-cols">
        <div class="card">
            <h3 class="card-title">Nuovo provvedimento</h3>

            {if !$can_add|default:false}
                <div class="empty-state">
                    {icon name='users' size=48}
                    <h3>Nessun pilota sanzionabile</h3>
                    {if $ambito == 'noleggio'}
                        <p>Puoi sanzionare solo i piloti che hanno già noleggiato un tuo veicolo
                           e che non hanno una sanzione attiva.</p>
                    {else}
                        <p>Puoi sanzionare solo i piloti che hanno già prenotato su un tuo circuito
                           e che non hanno una sanzione attiva.</p>
                    {/if}
                </div>
            {else}
                <div class="notification is-warning">
                    {icon name='alert'}
                    {if $ambito == 'noleggio'}
                        Applicando un ban o una sospensione, <strong>i noleggi futuri del pilota
                        con i tuoi veicoli vengono annullati con rimborso del 100%</strong>
                        (per la sospensione, solo quelli entro la data di fine). L'operazione non è reversibile.
                    {else}
                        Applicando un ban o una sospensione, <strong>le prenotazioni future del pilota
                        sui tuoi circuiti vengono annullate con rimborso del 100%</strong>
                        (per la sospensione, solo quelle entro la data di fine). L'operazione non è reversibile.
                    {/if}
                </div>

                <form method="post" action="{url path="`$base_path`/applica"}" class="form-stack is-wide">
                    {csrf_field}

                    <div class="form-row">
                        <label for="pilota_search">Pilota <span class="text-danger">*</span></label>
                        <div class="tp-combobox" data-combobox>
                            <input class="input" type="text" id="pilota_search" autocomplete="off"
                                   role="combobox" aria-autocomplete="list" aria-expanded="false"
                                   aria-controls="pilota_options" aria-haspopup="listbox"
                                   placeholder="Cerca un pilota per nome, cognome o email…"
                                   data-combobox-input>
                            <input type="hidden" name="pilota_id" id="pilota_id"
                                   value="{$form.pilota_id|default:''}" data-combobox-value>
                            <ul class="tp-combobox-list" id="pilota_options" role="listbox" data-combobox-list hidden>
                                {foreach from=$piloti item=p}
                                    <li id="pilota_opt_{$p.id}" class="tp-combobox-option" role="option"
                                        data-combobox-option
                                        data-value="{$p.id}"
                                        data-label="{$p.cognome|default:''} {$p.nome|default:''} — {$p.email|default:''}"
                                        data-search="{$p.cognome|default:''} {$p.nome|default:''} {$p.email|default:''}">
                                        <strong>{$p.cognome|default:''} {$p.nome|default:''}</strong>
                                        <span class="text-muted text-small">{$p.email|default:''}</span>
                                    </li>
                                {/foreach}
                                <li class="tp-combobox-empty" data-combobox-empty hidden>Nessun pilota corrisponde alla ricerca.</li>
                            </ul>
                        </div>
                        <p class="help text-muted">Inizia a digitare per cercare tra i piloti sanzionabili.</p>
                    </div>

                    <div class="form-row">
                        <label for="tipo">Provvedimento <span class="text-danger">*</span></label>
                        <div class="select is-fullwidth">
                            <select name="tipo" id="tipo" required
                                    data-toggle-target="#data_fine" data-toggle-enable-when="sospensione">
                                {assign var='tsel' value=$form.tipo|default:'ban'}
                                <option value="ban" {if $tsel == 'ban'}selected{/if}>Ban — permanente</option>
                                <option value="sospensione" {if $tsel == 'sospensione'}selected{/if}>Sospensione — a tempo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="data_fine">Fine sospensione</label>
                        <input class="input" type="date" name="data_fine" id="data_fine"
                               min="{$oggi}" value="{$form.data_fine|default:''}">
                        <p class="help text-muted">Obbligatoria solo per la sospensione; ignorata per il ban (permanente).</p>
                    </div>

                    <div class="form-row">
                        <label for="motivo">Motivazione <span class="text-danger">*</span></label>
                        <textarea class="textarea" name="motivo" id="motivo" maxlength="255" required
                                  placeholder="Es. condotta pericolosa in pista, mancato pagamento, comportamento scorretto...">{$form.motivo|default:''}</textarea>
                        {if $ambito == 'noleggio'}
                            <p class="help text-muted">Obbligatoria: verrà mostrata al pilota quando proverà a noleggiare.</p>
                        {else}
                            <p class="help text-muted">Obbligatoria: verrà mostrata al pilota quando proverà a prenotare.</p>
                        {/if}
                    </div>

                    <div class="flex">
                        <button class="button is-danger" type="submit">
                            {icon name='lock'} Applica provvedimento
                        </button>
                    </div>
                </form>
            {/if}
        </div>

        <div class="card tp-sanzioni-emesse">
            <h3 class="card-title">Provvedimenti emessi</h3>

            {if $empty_list|default:false}
                <div class="empty-state">
                    {icon name='users' size=56}
                    <h3>Nessun provvedimento</h3>
                    {if $ambito == 'noleggio'}
                        <p>Non hai ancora bannato o sospeso alcun pilota dai tuoi veicoli a noleggio.</p>
                    {else}
                        <p>Non hai ancora bannato o sospeso alcun pilota dai tuoi circuiti.</p>
                    {/if}
                </div>
            {else}
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Pilota</th>
                                <th>Provvedimento</th>
                                <th>Periodo</th>
                                <th>Stato</th>
                                <th>Motivazione</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach from=$sanzioni item=s}
                            {assign var='sid' value=$s.id|default:0}
                            {assign var='eff' value=$s.stato_effettivo|default:'attiva'}
                            <tr>
                                <td>
                                    <strong>{$s.pilota_cognome|default:''} {$s.pilota_nome|default:''}</strong>
                                    <div class="text-muted text-small">{$s.pilota_email|default:''}</div>
                                </td>
                                <td>
                                    {if $s.tipo|default:'' == 'ban'}
                                        <span class="tag is-danger">Ban</span>
                                    {else}
                                        <span class="tag is-warning">Sospensione</span>
                                    {/if}
                                </td>
                                <td>
                                    Dal {date_short sql=$s.data_inizio|default:''}
                                    {if $s.tipo|default:'' == 'ban'}
                                        · permanente
                                    {elseif !empty($s.data_fine)}
                                        → {date_short sql=$s.data_fine}
                                    {/if}
                                </td>
                                <td>
                                    {if $eff == 'attiva'}
                                        <span class="tag is-danger">Attiva</span>
                                    {elseif $eff == 'scaduta'}
                                        <span class="tag tp-tag-stato">Scaduta</span>
                                    {else}
                                        <span class="tag tp-tag-stato">Revocata</span>
                                    {/if}
                                </td>
                                <td>{$s.motivo|default:'—'}</td>
                                <td class="actions">
                                    {if $eff == 'attiva'}
                                        {assign var='stipo' value=$s.tipo|default:'ban'}
                                        <div class="tp-sanzione-tools">
                                            <details class="tp-sanzione-edit">
                                                <summary class="button is-warning is-small">
                                                    {icon name='edit'} Modifica periodo
                                                </summary>
                                                <form method="post" action="{url path="{$base_path}/{$sid}/modifica"}"
                                                      class="tp-sanzione-edit-form form-stack">
                                                    {csrf_field}
                                                    <div class="form-row">
                                                        <label for="tipo_{$sid}">Provvedimento</label>
                                                        <select name="tipo" id="tipo_{$sid}"
                                                                data-toggle-target="#data_fine_{$sid}"
                                                                data-toggle-enable-when="sospensione">
                                                            <option value="ban" {if $stipo == 'ban'}selected{/if}>Ban — permanente</option>
                                                            <option value="sospensione" {if $stipo == 'sospensione'}selected{/if}>Sospensione — a tempo</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-row">
                                                        <label for="data_fine_{$sid}">Fine sospensione</label>
                                                        <input class="input" type="date" name="data_fine" id="data_fine_{$sid}"
                                                               min="{$oggi}" value="{$s.data_fine|default:''}">
                                                        <p class="help text-muted">Obbligatoria per la sospensione; ignorata per il ban.</p>
                                                    </div>
                                                    <button class="button is-warning is-small" type="submit">
                                                        {icon name='check'} Aggiorna periodo
                                                    </button>
                                                </form>
                                            </details>
                                            <form method="post" action="{url path="{$base_path}/{$sid}/revoca"}" class="tp-sanzione-revoca">
                                                {csrf_field}
                                                <button class="button is-dark is-small" type="submit">
                                                    {icon name='check'} Revoca
                                                </button>
                                            </form>
                                        </div>
                                    {else}
                                        —
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
</div>
