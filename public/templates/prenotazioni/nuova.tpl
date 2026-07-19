{extends file='layouts/page.tpl'}

{block name=body}
{assign var='cid' value=$circuito_id}
{assign var='svaluta' value='EUR'}
{assign var='loc' value=$circuito.localita|default:''|trim}
{assign var='addr' value=$circuito.indirizzo|default:''|trim}
{assign var='luogoLead' value=($loc != '') ? $loc : $addr}
{assign var='lung' value=$circuito.lunghezza_km|default:null}
{assign var='iniDt' value=$state.inizio_sessione|default:''}
{assign var='finDt' value=$state.fine_sessione|default:''}
{assign var='noleggioOk' value=($veicoli|@count > 0)}

<div class="container">

    {capture assign='bc_circ_url'}/circuiti/{$cid}{/capture}
    {assign var='bc_nome' value=$circuito.nome_circuito|default:''}
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>$bc_nome,'url'=>$bc_circ_url],['label'=>'Prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Nuova prenotazione</h1>
            <p class="lead">
                {$circuito.nome_circuito|default:''}
                {if $luogoLead != ''} · {$luogoLead}{/if}
                {if $lung !== null && $lung !== ''}
                    · {$lung} km
                {/if}
            </p>
        </div>
    </div>

    <div class="steps">
        <div class="step{if $step == 1} active{/if}{if $step > 1} done{/if}">
            <span class="num">{if $step > 1}✓{else}1{/if}</span>
            <span class="label">Fascia oraria</span>
        </div>
        <div class="step{if $step == 2} active{/if}{if $step > 2} done{/if}">
            <span class="num">{if $step > 2}✓{else}2{/if}</span>
            <span class="label">Veicolo &amp; assicurazione</span>
        </div>
        <div class="step{if $step == 3} active{/if}">
            <span class="num">3</span>
            <span class="label">Pagamento</span>
        </div>
    </div>

    {valuta_switcher}

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err}</span></div>
    {/foreach}

    {if $step == 1}
        <form method="post" class="card form-stack tp-panel-medium">
            {csrf_field}
            <input type="hidden" name="circuito_id" value="{$cid}">
            <input type="hidden" name="form_step" value="1">

            <h3 class="card-title">Disponibilità richiesta</h3>
            <p class="text-muted text-small">
                Indica quando desideri utilizzare il circuito: la prenotazione viene confermata automaticamente al termine del pagamento.
            </p>
            <div class="form-row">
                <label>Inizio</label>
                <input type="datetime-local" name="inizio_sessione" required
                       value="{$iniDt|replace:' ':'T'}">
            </div>
            <div class="form-row">
                <label>Fine</label>
                <input type="datetime-local" name="fine_sessione" required
                       value="{$finDt|replace:' ':'T'}">
            </div>

            <hr class="divider">
            <h3 class="card-title">Modalità di partecipazione</h3>

            <label class="radio-card">
                <input type="radio" name="modalita" value="proprio" {if $state.modalita|default:'' == 'proprio'}checked{/if}>
                <strong>Veicolo proprio</strong>
                <div class="text-muted radio-card-desc">
                    Paghi la tariffa accesso pista del circuito (come da listino).
                </div>
            </label>
            <label class="radio-card">
                <input type="radio" name="modalita" value="noleggio"
                       {if $state.modalita|default:'' == 'noleggio'}checked{/if}
                       {if !$noleggioOk}disabled{/if}>
                <strong>Veicolo a noleggio</strong>
                <div class="text-muted radio-card-desc">
                    Aggiungi il listino del veicolo scelto (entità <em>Prezzo</em> nel modello).
                </div>
            </label>

            <button class="button is-primary is-fullwidth" type="submit">Continua {icon name='arrow-right'}</button>
        </form>

    {elseif $step == 2}
        <form method="post" class="card form-stack tp-panel-wide">
            {csrf_field}
            <input type="hidden" name="circuito_id" value="{$cid}">
            <input type="hidden" name="form_step" value="2">
            <input type="hidden" name="inizio_sessione" value="{$iniDt}">
            <input type="hidden" name="fine_sessione" value="{$finDt}">
            <input type="hidden" name="modalita" value="{$state.modalita|default:''}">

            {if $state.modalita|default:'' == 'proprio'}
                <h3 class="card-title">Veicolo proprio</h3>
                <div class="form-row">
                    <label for="targa">Targa</label>
                    <input type="text" id="targa" name="targa" value="{$state.targa|default:''}"
                           required maxlength="10" placeholder="Es. AB123CD"
                           class="input is-uppercase" autocapitalize="characters" autocomplete="off"
                           spellcheck="false"
                           pattern="{literal}[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{2}[0-9]{5}{/literal}"
                           title="Targa italiana: auto AA000AA oppure moto AA00000.">
                    <span class="help">Auto: AA000AA · Moto: AA00000.</span>
                </div>
            {else}
                <h3 class="card-title">Scegli il veicolo a noleggio</h3>
                {if $veicoli|@count == 0}
                    <div class="empty-state">
                        {icon name='car' size=56}
                        <h3>Nessun veicolo disponibile</h3>
                        <p>Per questo circuito non ci sono veicoli a noleggio attualmente.</p>
                    </div>
                {else}
                    <div class="grid grid-2">
                        {foreach from=$veicoli item=v}
                            {assign var='vid' value=$v.id|default:0}
                            <label class="radio-card">
                                <input type="radio" name="veicolo_id" value="{$vid}"
                                       {if $state.veicolo_id|default:0 == $vid}checked{/if} required>
                                <strong>{$v.marca|default:''} {$v.modello|default:''}</strong>
                                <div class="text-muted radio-card-desc">
                                    {tp_ucfirst s=$v.categoria|default:''}
                                    · {$v.potenza_cv|default:0}cv
                                    · cap. {$v.capienza|default:1}<br>
                                    Targa: {$v.targa|default:''}
                                </div>
                                <div class="mt-1 tp-price">
                                    {prezzo value=$v.prezzo|default:0}
                                </div>
                            </label>
                        {/foreach}
                    </div>
                {/if}
            {/if}

            <hr class="divider">
            <label class="inline">
                <input type="checkbox" name="assicurazione" value="1" {if $state.assicurazione}checked{/if}>
                Aggiungi assicurazione (+ {prezzo value=$prezzo_assicurazione})
            </label>

            <div class="flex">
                <a class="button is-dark" href="{url path="/circuiti/{$cid}?step=1"}">
                    {icon name='arrow-left'} Indietro
                </a>
                <button class="button is-primary" type="submit">Continua al pagamento {icon name='arrow-right'}</button>
            </div>
        </form>

    {elseif $step == 3}
        <div class="detail-grid">
            <form method="post" class="card form-stack is-wide" data-cc-form novalidate>
                {csrf_field}
                <input type="hidden" name="circuito_id" value="{$cid}">
                <input type="hidden" name="form_step" value="3">
                <input type="hidden" name="inizio_sessione" value="{$iniDt}">
                <input type="hidden" name="fine_sessione" value="{$finDt}">
                <input type="hidden" name="modalita" value="{$state.modalita|default:''}">
                <input type="hidden" name="targa" value="{$state.targa|default:''}">
                <input type="hidden" name="veicolo_id" value="{$state.veicolo_id|default:0}">
                <input type="hidden" name="assicurazione" value="{if $state.assicurazione}1{else}0{/if}">

                <h3 class="card-title">Dati di pagamento</h3>
                <div class="grid grid-2">
                    <div class="form-row">
                        <label for="cc_nome">Nome titolare</label>
                        <input type="text" id="cc_nome" name="cc_nome" required maxlength="80"
                               autocomplete="cc-given-name" autocapitalize="words" spellcheck="false"
                               pattern="{literal}[\p{L}][\p{L}'’.\- ]{1,79}{/literal}" data-cc-name>
                    </div>
                    <div class="form-row">
                        <label for="cc_cognome">Cognome titolare</label>
                        <input type="text" id="cc_cognome" name="cc_cognome" required maxlength="80"
                               autocomplete="cc-family-name" autocapitalize="words" spellcheck="false"
                               pattern="{literal}[\p{L}][\p{L}'’.\- ]{1,79}{/literal}" data-cc-surname>
                    </div>
                </div>
                <div class="form-row">
                    <label for="cc_numero">Numero carta</label>
                    <input type="text" id="cc_numero" name="cc_numero" required inputmode="numeric"
                           maxlength="23" placeholder="1234 5678 9012 3456" autocomplete="cc-number"
                           pattern="{literal}[0-9 ]{13,23}{/literal}" data-cc-number>
                </div>
                <div class="grid grid-2">
                    <div class="form-row">
                        <label for="cc_scad">Scadenza</label>
                        <input type="text" id="cc_scad" name="cc_scad" required inputmode="numeric"
                               maxlength="7" placeholder="MM/AAAA" autocomplete="cc-exp"
                               pattern="{literal}\d{2}/\d{4}{/literal}" data-cc-exp>
                        <span class="help">Mese e anno (la barra viene inserita in automatico).</span>
                    </div>
                    <div class="form-row">
                        <label for="cc_cvv">CVV</label>
                        <input type="password" id="cc_cvv" name="cc_cvv" required inputmode="numeric"
                               maxlength="4" placeholder="•••" autocomplete="cc-csc"
                               pattern="{literal}\d{3,4}{/literal}" data-cc-cvv>
                        <span class="help">3 cifre (4 per American Express).</span>
                    </div>
                </div>

                <div class="flex">
                    <a class="button is-dark" href="{url path="/circuiti/{$cid}?step=2"}">
                        {icon name='arrow-left'} Indietro
                    </a>
                    <button class="button is-primary" type="submit">
                        {icon name='lock'} Conferma e paga {prezzo value=$totale currency=$svaluta}
                    </button>
                </div>
            </form>

            <aside class="card">
                <h3 class="card-title">Riepilogo</h3>
                <p><strong>Codice:</strong> (assegnato al salvataggio)</p>
                <p><strong>Circuito:</strong> {$circuito.nome_circuito|default:''}</p>
                <p><strong>Da:</strong> {$iniDt}</p>
                <p><strong>A:</strong> {$finDt}</p>
                <p><strong>Accesso pista:</strong> {prezzo value=$sessione.tariffa_accesso|default:0 currency=$svaluta}</p>
                <p><strong>Modalità:</strong> {$state.modalita|default:''}</p>
                {if $state.modalita|default:'' == 'proprio'}
                    <p><strong>Targa:</strong> {$state.targa|default:''}</p>
                {elseif $veicolo_riep}
                    <p><strong>Veicolo:</strong> {$veicolo_riep.marca|default:''} {$veicolo_riep.modello|default:''}
                        ({$veicolo_riep.targa|default:''}) –
                        {prezzo value=$veicolo_riep.prezzo|default:0}</p>
                {/if}
                <p><strong>Assicurazione:</strong>
                    {if $state.assicurazione}Si ({prezzo value=$prezzo_assicurazione}){else}No{/if}</p>
                {if $promozione}
                    <p><strong>Promozione:</strong> {$promozione.titolo|default:''}</p>
                    {if !empty($promozione.descrizione)}
                        <p class="text-muted promo-applied-desc">{$promozione.descrizione|escape|nl2br nofilter}</p>
                    {/if}
                    <p><strong>Sconto automatico:</strong> -{prezzo value=$sconto_promozione currency=$svaluta}</p>
                {/if}
                <hr class="divider">
                <p class="tp-summary-total">
                    Totale: {prezzo value=$totale currency=$svaluta}
                </p>
            </aside>
        </div>
    {/if}
</div>
{/block}
