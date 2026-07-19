{extends file='layouts/page.tpl'}

{block name=body}
{assign var='ep' value=$edit_promo|default:null}
<div class="container">
    {back_button href=$dash_back|default:'/dashboard' label='Dashboard'}

    <div class="page-header">
        <div>
            <h1>Promozioni</h1>
            <p class="lead">
                Le promozioni possono essere create da un'<strong>azienda di noleggio</strong>
                o da un <strong>gestore di circuiti</strong> e vengono applicate automaticamente ai destinatari.
            </p>
        </div>
    </div>

    {if $elimina_promo|default:null}
        {assign var='del' value=$elimina_promo}
        {capture assign='del_msg'}Stai per eliminare la promozione «{$del.titolo|escape}». L'operazione non è reversibile.{/capture}
        {include file='partials/confirm_delete.tpl'
            confirm_title='Elimina promozione'
            confirm_message=$del_msg
            confirm_cancel_url="{url path='/promozioni'}"
            confirm_action='elimina'
            confirm_item_id=$del.id|default:0
            confirm_ok_label='Elimina promozione'
        }
    {/if}

    <div class="detail-grid">
        <div>
            {if $promo|@count == 0}
                <div class="empty-state">
                    {icon name='tag' size=56}
                    <h3>Nessuna promozione</h3>
                    <p>{if $can_create}Crea la tua prima offerta dal form a destra.{else}Non hai circuiti associati su cui creare promozioni.{/if}</p>
                </div>
            {else}
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr>
                            <th>Titolo</th><th>Descrizione</th><th>Riferimento</th><th>Codice</th><th>Sconto</th>
                            <th>Destinatari</th><th>Validità</th>
                            {if $can_create}<th></th>{/if}
                        </tr></thead>
                        <tbody>
                        {foreach from=$promo item=p}
                            {assign var='pid' value=$p.id|default:0}
                            <tr{if $ep && $ep.id|default:0 == $pid} class="is-selected"{/if}>
                                <td><strong>{$p.titolo|default:''}</strong></td>
                                <td class="promo-table-desc">
                                    {if !empty($p.descrizione)}
                                        {$p.descrizione|escape|nl2br nofilter}
                                    {else}
                                        <span class="text-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    {if !empty($p.nome_veicolo)}
                                        {$p.nome_veicolo|default:''}
                                    {else}
                                        {$p.nome_circuito|default:''}
                                    {/if}
                                </td>
                                <td><code>{$p.codice|default:''}</code></td>
                                <td>
                                    {$p.valore|default:''}{if $p.tipo_sconto|default:'' == 'percentuale'}%{else}€{/if}
                                </td>
                                <td>
                                    {if !empty($p.soglia_prenotazioni)}
                                        Almeno {$p.soglia_prenotazioni} prenotazioni
                                    {else}
                                        Tutti gli utenti
                                    {/if}
                                </td>
                                <td>
                                    {if !empty($p.data_inizio) && !empty($p.data_fine)}
                                        {$p.data_inizio} →<br>{$p.data_fine}
                                    {elseif !empty($p.data_prenotazione_inizio) && !empty($p.data_prenotazione_fine)}
                                        {$p.data_prenotazione_inizio} →<br>{$p.data_prenotazione_fine}
                                    {else}
                                        <span class="text-muted">Sempre valida</span>
                                    {/if}
                                </td>
                                {if $can_create}
                                <td class="actions">
                                    <a href="{url path='/promozioni'}?edit={$pid}"
                                       class="button is-dark is-small tp-icon-btn"
                                       title="Modifica" aria-label="Modifica">
                                        {icon name='edit'}
                                    </a>
                                    <a href="{url path='/promozioni'}?elimina={$pid}"
                                       class="button is-danger is-small tp-icon-btn"
                                       title="Elimina" aria-label="Elimina">
                                        {icon name='trash'}
                                    </a>
                                </td>
                                {/if}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>

        {if $can_create}
        <aside class="card promo-form-card">
            <div class="promo-form-header">
                <div class="promo-form-icon">{icon name='tag' size=22}</div>
                <div>
                    <h3 class="card-title mb-0">{if $ep}Modifica promozione{else}Nuova promozione{/if}</h3>
                    {if !$ep}<p class="text-muted text-small mb-0">Compila i campi e pubblica l'offerta.</p>{/if}
                </div>
            </div>
            <form method="post" class="form-stack is-wide">
                {csrf_field}
                <input type="hidden" name="azione" value="{if $ep}modifica{else}crea{/if}">
                {if $ep}<input type="hidden" name="id" value="{$ep.id|default:0}">{/if}
                <div class="form-row">
                    <label>Titolo</label>
                    <input name="titolo" required maxlength="150" value="{$ep.titolo|default:''|escape}">
                </div>
                <div class="form-row">
                    {if $ruolo == 'gestore_noleggio'}
                        <label>Veicolo</label>
                        <select name="veicolo_id" required>
                            <option value="">Seleziona un veicolo</option>
                            {foreach from=$veicoli item=v}
                                <option value="{$v.id|default:0}"
                                    {if $ep && $ep.veicolo_noleggio_id|default:0 == $v.id|default:0}selected{/if}>
                                    {$v.marca|default:''} {$v.modello|default:''} ({$v.targa|default:''})
                                </option>
                            {/foreach}
                        </select>
                    {else}
                        <label>Circuito</label>
                        <select name="circuito_id" required>
                            <option value="">Seleziona un circuito</option>
                            {foreach from=$circuiti item=c}
                                <option value="{$c.id|default:0}"
                                    {if $ep && $ep.circuito_id|default:0 == $c.id|default:0}selected{/if}>
                                    {$c.nome_circuito|default:''}
                                </option>
                            {/foreach}
                        </select>
                    {/if}
                </div>
                <div class="grid grid-2">
                    <div class="form-row"><label>Tipo sconto</label>
                        <select name="tipo_sconto">
                            <option value="percentuale" {if $ep && $ep.tipo_sconto|default:'' == 'percentuale'}selected{/if}>Percentuale</option>
                            <option value="importo" {if $ep && $ep.tipo_sconto|default:'' == 'importo'}selected{/if}>Importo fisso</option>
                        </select>
                    </div>
                    <div class="form-row"><label>Valore</label>
                        <input type="number" step="0.01" min="0.01" inputmode="decimal" name="valore" required
                               value="{$ep.valore|default:''|escape}">
                    </div>
                    <div class="form-row"><label>Inizio validità (facoltativo)</label>
                        <input type="date" name="data_inizio" value="{$ep.data_inizio|default:''|escape}">
                    </div>
                    <div class="form-row"><label>Fine validità (facoltativo)</label>
                        <input type="date" name="data_fine" value="{$ep.data_fine|default:''|escape}">
                    </div>
                </div>
                <div class="form-row"><label>Soglia prenotazioni (facoltativa)</label>
                    <input type="number" name="soglia_prenotazioni" min="1" step="1" inputmode="numeric"
                           placeholder="Es. 3"
                           value="{$ep.soglia_prenotazioni|default:''|escape}">
                </div>
                <p class="text-muted text-small mb-2">
                    Periodo di validità e soglia prenotazioni sono entrambi facoltativi e cumulabili:
                    puoi attivare la promozione solo per un periodo, solo per utenti con un certo storico,
                    oppure con entrambe le condizioni insieme.
                </p>
                <div class="form-row"><label>Descrizione</label>
                    <textarea name="descrizione">{$ep.descrizione|default:''|escape}</textarea>
                </div>
                {if $ep && $ep.codice|default:''}
                    <p class="text-muted text-small mb-2">Codice promozione: <code>{$ep.codice|escape}</code></p>
                {/if}
                <div class="promo-form-actions">
                    <button class="button is-primary tp-promo-submit" type="submit">
                        {if $ep}{icon name='check'} Salva modifiche{else}{icon name='plus'} Crea promozione{/if}
                    </button>
                    {if $ep}
                        <a class="button is-dark" href="{url path='/promozioni'}">Annulla</a>
                    {/if}
                </div>
            </form>
        </aside>
        {else}
        <aside class="card">
            <p class="text-muted mb-0">Non hai circuiti associati su cui creare una promozione.</p>
        </aside>
        {/if}
    </div>
</div>
{/block}
