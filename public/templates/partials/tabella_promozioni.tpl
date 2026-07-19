{if $promo|@count == 0}
    <div class="empty-state">
        {icon name='tag' size=56}
        <h3>Nessuna promozione</h3>
        <p>Non ci sono ancora offerte per questo servizio.</p>
        <a class="button is-primary mt-3" href="{url path="/promozioni/servizio/{$entita_id|default:0}/nuova"}">
            {icon name='plus'} Crea la prima promozione
        </a>
    </div>
{else}
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th>Titolo</th><th>Descrizione</th><th>Codice</th><th>Sconto</th>
                <th>Destinatari</th><th>Validità</th><th>Azioni</th>
            </tr></thead>
            <tbody>
            {foreach from=$promo item=p}
                {assign var='pid' value=$p.id|default:0}
                <tr>
                    <td><strong>{$p.titolo|default:''}</strong></td>
                    <td class="promo-table-desc">
                        {if !empty($p.descrizione)}
                            {$p.descrizione|escape|nl2br nofilter}
                        {else}
                            <span class="text-muted">—</span>
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
                        {else}
                            <span class="text-muted">Sempre valida</span>
                        {/if}
                    </td>
                    <td class="actions">
                        <a href="{url path="/promozioni/{$pid}/modifica"}"
                           class="button is-dark is-small tp-icon-btn"
                           title="Modifica" aria-label="Modifica promozione">
                            {icon name='edit'}
                        </a>
                        <form method="post" action="{url path="/promozioni/{$pid}/elimina"}"
                              class="inline-form"
                              onsubmit="return confirm('Eliminare definitivamente questa promozione? L\'operazione non è reversibile.');">
                            {csrf_field}
                            <button type="submit" class="button is-danger is-small tp-icon-btn"
                                    title="Elimina" aria-label="Elimina promozione">
                                {icon name='trash'}
                            </button>
                        </form>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}
