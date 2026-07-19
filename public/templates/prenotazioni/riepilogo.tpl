{extends file='layouts/page.tpl'}

{block name=body}
{assign var='sid' value=$sessione.id|default:0}
{assign var='svaluta' value='EUR'}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Riepilogo prenotazione</h1>
            <p class="lead">Verifica i dati e scegli l'assicurazione</p>
        </div>
    </div>

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
    {/foreach}

    {valuta_switcher}

    <div class="detail-grid detail-grid--checkout">
        <form method="post" action="{url path='/prenotazione/pagamento'}" class="card form-stack is-wide" data-cc-form novalidate>
            {csrf_field}
            <h3 class="card-title">Dati di pagamento</h3>

            {if $carte_salvate|default:[]|@count > 0}
                {* Riuso di una carta salvata in precedenza: selezionandola, i campi
                   della nuova carta vengono nascosti e disabilitati. *}
                <div class="form-row">
                    <label>Metodo di pagamento</label>
                    <label class="tp-radio-row">
                        <input type="radio" name="carta_salvata_id" value="0" checked data-carta-radio>
                        Usa una nuova carta
                    </label>
                    {foreach from=$carte_salvate item=c}
                        <label class="tp-radio-row">
                            <input type="radio" name="carta_salvata_id" value="{$c.id}" data-carta-radio>
                            {$c.numero_masked|escape} — {$c.nome_titolare|escape} {$c.cognome_titolare|escape}
                            (scad. {$c.data_scadenza|escape})
                        </label>
                    {/foreach}
                </div>
            {else}
                <input type="hidden" name="carta_salvata_id" value="0">
            {/if}

            <div data-cc-nuova>
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
            <div class="form-row">
                <label class="tp-radio-row">
                    <input type="checkbox" name="salva_carta" value="1">
                    Salva questa carta per i prossimi pagamenti
                </label>
                <span class="help">Se non la salvi, i dati della carta non vengono memorizzati.</span>
            </div>
            </div>

            {* L'assicurazione è già scelta nel passo precedente (pagina veicolo/targa):
               qui viaggia solo come valore nascosto, senza ri-chiederla. *}
            <input type="hidden" name="assicurazione" value="{if $state.assicurazione}1{else}0{/if}">

            <button class="button is-primary mt-3" type="submit">
                {icon name='lock'} Conferma e paga {prezzo value=$totale currency=$svaluta}
            </button>

            {if $carte_salvate|default:[]|@count > 0}
            <script>
            (function () {
                var radios = document.querySelectorAll('[data-carta-radio]');
                var nuova  = document.querySelector('[data-cc-nuova]');
                if (!nuova) { return; }
                function sync() {
                    var scelta   = document.querySelector('[data-carta-radio]:checked');
                    var usaNuova = !scelta || scelta.value === '0';
                    nuova.style.display = usaNuova ? '' : 'none';
                    nuova.querySelectorAll('input').forEach(function (inp) {
                        inp.disabled = !usaNuova;
                    });
                }
                radios.forEach(function (r) { r.addEventListener('change', sync); });
                sync();
            })();
            </script>
            {/if}
        </form>

        <aside class="card">
            <h3 class="card-title">Riepilogo</h3>
            {if $circuito}
                <p><strong>Circuito:</strong> {$circuito.nome_circuito|default:''|escape}</p>
            {/if}
            {if $sessione}
                <p><strong>Sessione:</strong> {$sessione.inizio|default:''|escape} → {$sessione.fine|default:''|escape}</p>
            {/if}
            <p><strong>Accesso pista:</strong> {prezzo value=$sessione.tariffa_accesso|default:0 currency=$svaluta}</p>
            <p><strong>Modalità:</strong> {$state.modalita|default:''|escape}</p>
            {if $state.modalita|default:'' == 'proprio'}
                <p><strong>Targa:</strong> {$state.targa|default:''|escape}</p>
            {elseif $veicolo_riep}
                <p><strong>Veicolo:</strong> {$veicolo_riep.marca|default:''|escape} {$veicolo_riep.modello|default:''|escape}
                    ({$veicolo_riep.targa|default:''|escape}) –
                    {prezzo value=$veicolo_riep.prezzo|default:0}</p>
            {/if}
            <p><strong>Assicurazione:</strong>
                {if $state.assicurazione}Sì (+ {prezzo value=$prezzo_assicurazione currency=$svaluta}){else}No{/if}</p>
            {if $promozione}
                <p><strong>Promozione:</strong> {$promozione.titolo|default:''|escape}</p>
                <p><strong>Sconto:</strong> -{prezzo value=$sconto_promozione currency=$svaluta}</p>
            {/if}
            <hr class="divider">
            <p class="tp-summary-total">Totale: {prezzo value=$totale currency=$svaluta}</p>
        </aside>
    </div>
</div>
{/block}
