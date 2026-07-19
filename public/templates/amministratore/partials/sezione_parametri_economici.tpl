<section id="parametri-economici" class="tp-commissioni-section" aria-labelledby="parametri-economici-title">
    <div class="tp-section-head">
        <div class="tp-section-head-text">
            <p class="tp-section-eyebrow">Parametri economici</p>
            <h2 id="parametri-economici-title" class="tp-section-title">Assicurazione e commissioni</h2>
            <p class="tp-section-desc">
                {if $anteprima|default:false}
                    Anteprima attiva: verifica i valori proposti prima del salvataggio definitivo.
                {else}
                    Monitora i parametri in vigore e consulta lo storico delle modifiche.
                {/if}
            </p>
        </div>
        {if $anteprima|default:false}
            <span class="badge in_attesa tp-commissioni-badge">Anteprima</span>
        {/if}
    </div>

    {if $anteprima|default:false}
        <div class="notification is-info tp-flash mb-3">
            {flash_icon type='info'}
            <span>Stai visualizzando un'anteprima: i valori non sono ancora definitivi.</span>
        </div>
    {/if}

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
    {/foreach}

    <div class="tp-commissioni-kpi-grid">
        <div class="kpi tp-commissioni-kpi">
            <div class="tp-commissioni-kpi-icon">{icon name='lock' class='kpi-icon'}</div>
            <div class="tp-commissioni-kpi-body">
                <div class="kpi-value">{money value=$prezzo}</div>
                <div class="kpi-label">Prezzo assicurazione</div>
            </div>
        </div>
        <div class="kpi tp-commissioni-kpi">
            <div class="tp-commissioni-kpi-icon">{icon name='gauge' class='kpi-icon'}</div>
            <div class="tp-commissioni-kpi-body">
                <div class="kpi-value">{$perc|string_format:"%.2f"}%</div>
                <div class="kpi-label">Commissione piattaforma</div>
            </div>
        </div>
        <div class="kpi tp-commissioni-kpi">
            <div class="tp-commissioni-kpi-icon">{icon name='percent' class='kpi-icon'}</div>
            <div class="tp-commissioni-kpi-body">
                <div class="kpi-value">{$aliquota|default:0|string_format:"%.2f"}%</div>
                <div class="kpi-label">Aliquota IVA</div>
            </div>
        </div>
        <div class="kpi tp-commissioni-kpi">
            <div class="tp-commissioni-kpi-icon">{icon name='receipt' class='kpi-icon'}</div>
            <div class="tp-commissioni-kpi-body">
                <div class="kpi-value">{money value=$ricavo_assic}</div>
                <div class="kpi-label">Ricavi assicurazione</div>
            </div>
        </div>
    </div>

    <div class="tp-commissioni-charts">
        <div class="card tp-commissioni-stat-card">
            <h3 class="card-title">Commissione piattaforma</h3>
            <div class="tp-bar-chart" aria-hidden="true">
                <div class="tp-bar-fill" style="width: {$grafici.commissione_pct|default:0}%"></div>
            </div>
            <p class="tp-commissioni-stat-caption">
                <span class="tp-commissioni-stat-value">{$grafici.commissione_pct|default:0|string_format:"%.2f"}%</span>
                sulle transazioni
            </p>
        </div>
        <div class="card tp-commissioni-stat-card">
            <h3 class="card-title">Assicurazioni attive</h3>
            <div class="tp-bar-chart" aria-hidden="true">
                {assign var='max_assic' value=$grafici.tot_assicurazioni|default:1}
                {if $max_assic < 1}{assign var='max_assic' value=1}{/if}
                {assign var='assic_pct' value=($grafici.tot_assicurazioni|default:0 / $max_assic) * 100}
                <div class="tp-bar-fill is-accent" style="width: {$assic_pct|string_format:"%.1f"}%"></div>
            </div>
            <p class="tp-commissioni-stat-caption">
                <span class="tp-commissioni-stat-value">{$grafici.tot_assicurazioni|default:0}</span>
                prenotazioni con assicurazione
            </p>
        </div>
    </div>

    <div class="tp-commissioni-layout">
        <div class="card tp-commissioni-form-panel">
            <h3 class="card-title">Modifica parametri</h3>
            <p class="tp-section-desc mb-0">Inserisci i nuovi valori e usa l'anteprima prima di confermare.</p>

            <form method="post" action="{url path='/commissioni/anteprima'}" class="form-stack tp-commissioni-form">
                {csrf_field}
                <div class="form-row">
                    <label for="prezzo_assicurazione">Prezzo assicurazione (EUR)</label>
                    <input class="input" type="text" id="prezzo_assicurazione" name="prezzo_assicurazione"
                           value="{$form_prezzo}" required inputmode="decimal" placeholder="es. 12,50">
                </div>
                <div class="form-row">
                    <label for="percentuale_commissione">Percentuale commissione (%)</label>
                    <input class="input" type="text" id="percentuale_commissione" name="percentuale_commissione"
                           value="{$form_perc}" required inputmode="decimal" placeholder="es. 5,00">
                </div>
                <div class="form-row">
                    <label for="aliquota_iva">Aliquota IVA (%)</label>
                    <input class="input" type="text" id="aliquota_iva" name="aliquota_iva"
                           value="{$form_aliquota}" required inputmode="decimal" placeholder="es. 22,00">
                    <span class="help">Applicata alle fatture emesse dalla piattaforma per le prenotazioni future.</span>
                </div>
                {if !($anteprima|default:false)}
                    <div class="tp-commissioni-actions">
                        <button class="button is-dark" type="submit">{icon name='eye'} Anteprima modifiche</button>
                    </div>
                {/if}
            </form>

            {if $anteprima|default:false}
                <form method="post" action="{url path='/commissioni/riepilogo'}" class="form-stack tp-commissioni-form tp-commissioni-form-confirm">
                    {csrf_field}
                    <input type="hidden" name="prezzo_assicurazione" value="{$form_prezzo}">
                    <input type="hidden" name="percentuale_commissione" value="{$form_perc}">
                    <input type="hidden" name="aliquota_iva" value="{$form_aliquota}">
                    <button class="button is-primary" type="submit">{icon name='check'} Salva modifiche</button>
                </form>
            {/if}
        </div>

        <aside class="card tp-commissioni-storico-panel">
            <div class="tp-storico-block">
                <h3 class="card-title">Ultime modifiche</h3>
                {if $storia|@count == 0}
                    <p class="tp-storico-empty">Nessuna modifica.</p>
                {else}
                    <div class="table-wrap is-ghost tp-storico-table-wrap">
                        <table class="table is-ghost tp-storico-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Assicur.</th>
                                    <th>Comm.%</th>
                                    <th>IVA%</th>
                                </tr>
                            </thead>
                            <tbody>
                            {foreach from=$storia item=h}
                                <tr>
                                    <td>{date_pretty sql=$h.aggiornato_il|default:''}</td>
                                    <td>{money value=$h.prezzo_assicurazione|default:0}</td>
                                    <td>{$h.percentuale_commissione|default:0|string_format:"%.2f"}%</td>
                                    <td>{$h.aliquota_iva|default:0|string_format:"%.2f"}%</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </div>
        </aside>
    </div>
</section>
