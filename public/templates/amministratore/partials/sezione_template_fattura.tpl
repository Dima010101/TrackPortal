{assign var='vista' value=$tpl.vista|default:'pilota'}
{assign var='etichetta' value=$tpl.etichetta|default:$vista}
{assign var='is_fattura' value=$tpl.is_fattura|default:false}
{assign var='meta' value=$tpl.meta|default:[]}
{assign var='viste' value=$tpl.viste|default:[]}

<section id="template-fattura" class="tp-template-section" aria-labelledby="template-fattura-title">
    <div class="tp-section-head">
        <div class="tp-section-head-text">
            <p class="tp-section-eyebrow">Documenti PDF</p>
            <h2 id="template-fattura-title" class="tp-section-title">Template ricevute e fatture</h2>
            <p class="tp-section-desc">
                Layout HTML/Smarty dei documenti scaricabili: le <strong>ricevute</strong> (pilota, gestore, noleggio)
                e la <strong>fattura</strong> fiscale. L'anteprima usa dati dimostrativi.
            </p>
        </div>
        <div class="tp-template-tabs">
            {assign var='gruppo_corrente' value=''}
            {foreach from=$viste item=v name=tabs}
                {if $v.gruppo != $gruppo_corrente}
                    {if $gruppo_corrente != ''}</div></div>{/if}
                    <div class="tp-template-tab-group">
                        <span class="tp-template-tab-group-label">{$v.gruppo|escape}</span>
                        <div class="btn-group" role="tablist" aria-label="Template {$v.gruppo|escape}">
                    {assign var='gruppo_corrente' value=$v.gruppo}
                {/if}
                <a href="{url path='/fatture'}?vista={$v.key|escape:url}#template-fattura"
                   class="{if $vista == $v.key}is-active{/if}"
                   role="tab"
                   aria-selected="{if $vista == $v.key}true{else}false{/if}">{$v.label|escape}</a>
                {if $smarty.foreach.tabs.last}</div></div>{/if}
            {/foreach}
        </div>
    </div>

    {if $tpl.errors|default:[]|@count > 0}
        <div class="notification is-danger mb-3">
            <ul class="tp-error-list">
                {foreach from=$tpl.errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <div class="tp-template-layout">
        <div class="card tp-template-panel">
            <div class="tp-template-panel-head">
                <h3 class="card-title mb-0">{$etichetta|escape}</h3>
                {if $meta.origine|default:'' == 'personalizzato'}
                    <span class="badge confermata tp-template-badge">Personalizzato</span>
                {else}
                    <span class="badge completata tp-template-badge">Predefinito</span>
                {/if}
            </div>

            <dl class="tp-template-meta">
                <div class="tp-meta-item">
                    <dt>Documento</dt>
                    <dd>{$etichetta|escape}</dd>
                </div>
                <div class="tp-meta-item">
                    <dt>Path</dt>
                    <dd><code class="tp-code-inline">{$meta.smarty_path|default:'—'|escape}</code></dd>
                </div>
                <div class="tp-meta-item">
                    <dt>Ultimo file</dt>
                    <dd>{$meta.nome_originale|default:'—'|escape}</dd>
                </div>
                <div class="tp-meta-item">
                    <dt>Aggiornato</dt>
                    <dd>{if !empty($meta.modificato_il)}{date_pretty sql=$meta.modificato_il}{else}—{/if}</dd>
                </div>
                <div class="tp-meta-item">
                    <dt>Dimensione</dt>
                    <dd>{if $meta.dimensione|default:0 > 0}{$meta.dimensione} byte{else}—{/if}</dd>
                </div>
            </dl>
        </div>

        <div class="card tp-template-panel">
            <h3 class="card-title">Carica nuovo template</h3>
            <p class="tp-template-upload-hint">
                Formati ammessi: <strong>.html</strong>, <strong>.htm</strong>, <strong>.tpl</strong> · max 512 KB.
                La versione precedente viene archiviata automaticamente.
            </p>
            {if $is_fattura}
                <p class="tp-template-upload-hint">
                    Stai modificando il layout della <strong>fattura fiscale</strong>: lo stesso documento
                    vale per tutti gli emittenti (circuito, noleggio e piattaforma), perché cambiano solo
                    i dati, non l'impaginazione.
                </p>
                <p class="tp-template-upload-hint">
                    Nel file puoi richiamare due variabili Smarty con i dati del documento:
                </p>
                <ul class="tp-template-vars">
                    <li><code class="tp-code-inline">$doc</code> — intestazione del documento: numero, data di
                        emissione, dati di emittente e cliente, totali e imposta di bollo.</li>
                    <li><code class="tp-code-inline">$righe</code> — elenco delle voci, ognuna con descrizione,
                        imponibile, aliquota IVA e imposta.</li>
                </ul>
            {else}
                <p class="tp-template-upload-hint">
                    Stai modificando il layout della <strong>ricevuta «{$etichetta|escape}»</strong>,
                    un documento riepilogativo non fiscale.
                </p>
                <p class="tp-template-upload-hint">
                    Nel file puoi richiamare la variabile Smarty con i dati della prenotazione:
                </p>
                <ul class="tp-template-vars">
                    <li><code class="tp-code-inline">$pren</code> — tutti i dati della prenotazione: circuito,
                        date di inizio/fine, importi, veicolo ed eventuale promozione.</li>
                </ul>
            {/if}
            <form method="post" action="{url path='/templateFattura/carica'}"
                  enctype="multipart/form-data" class="form-stack tp-template-upload">
                {csrf_field}
                <input type="hidden" name="vista" value="{$vista|escape}">
                <div class="form-row">
                    <label for="file_template_{$vista|escape}">Seleziona file</label>
                    <input type="file" id="file_template_{$vista|escape}" name="file_template" required
                           accept=".html,.htm,.tpl,text/html" class="input tp-file-input">
                </div>
                <button type="submit" class="button is-primary is-fullwidth">
                    {icon name='upload'} Carica e sostituisci
                </button>
            </form>
        </div>
    </div>

    {if $tpl.anteprima && !empty($tpl.anteprima.html)}
        <div class="card tp-template-preview-card">
            <div class="tp-template-preview-head">
                <h3 class="card-title mb-0">Anteprima PDF</h3>
                <span class="tp-template-preview-caption">Render con dati dimostrativi</span>
            </div>
            <div class="tp-template-preview-frame">
                <div class="tp-template-preview-sheet">
                    {$tpl.anteprima.html nofilter}
                </div>
            </div>
        </div>
    {/if}
</section>
