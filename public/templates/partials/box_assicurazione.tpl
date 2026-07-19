{*
  Box esplicativo dell'assicurazione pista + toggle di scelta.
  Usato a destra nelle pagine del wizard di prenotazione:
   - conferma_sessione.tpl (Opzione A, inserimento targa veicolo proprio)
   - veicoli_noleggio.tpl  (Opzione B, scelta veicolo a noleggio)

  Parametri:
   - prezzo_assicurazione : importo del sovrapprezzo (EUR)
   - assicurazione_on     : bool, se il toggle parte selezionato (dallo stato)

  NB: le percentuali di rimborso citate riflettono EPrenotazione::percentualeRimborso
  (assicurato 100%; senza assicurazione a fasce: max 90%, fino al 30% sotto 24h).
*}
<aside class="card tp-insurance-box">
    <div class="tp-insurance-box__head">
        {icon name='lock' size=20 class='tp-insurance-box__icon'}
        <h3 class="card-title mb-0">Assicurazione pista</h3>
    </div>

    <p class="text-muted text-small tp-insurance-box__intro">
        Con un piccolo sovrapprezzo proteggi la tua giornata in pista: niente brutte
        sorprese in caso di danni e rimborso pieno se cambi programma.
    </p>

    <ul class="tp-insurance-benefits">
        <li>
            {icon name='check' size=16 class='tp-insurance-benefits__ico'}
            <span><strong>Copertura danni</strong> al veicolo (proprio o a noleggio)
            durante la sessione in pista.</span>
        </li>
        <li>
            {icon name='check' size=16 class='tp-insurance-benefits__ico'}
            <span><strong>Esonero responsabilità</strong>: nessun addebito per i danni
            accidentali al mezzo entro il massimale previsto.</span>
        </li>
        <li>
            {icon name='check' size=16 class='tp-insurance-benefits__ico'}
            <span><strong>Rimborso 100% in caso di annullamento</strong>, a prescindere
            dall'anticipo. Senza assicurazione il rimborso è a fasce sull'anticipo
            (max 90%, fino al 30% sotto le 24 ore).</span>
        </li>
        <li>
            {icon name='check' size=16 class='tp-insurance-benefits__ico'}
            <span><strong>Assistenza prioritaria</strong> in caso di problemi tecnici
            prima o durante la sessione.</span>
        </li>
    </ul>

    <label class="inline tp-insurance-toggle">
        <input type="checkbox" name="assicurazione" value="1"
               {if $assicurazione_on|default:false}checked{/if}>
        <span>Aggiungi assicurazione
            <strong>(+ {prezzo value=$prezzo_assicurazione|default:0})</strong></span>
    </label>
    <p class="text-muted text-small tp-insurance-box__note">
        Puoi modificare questa scelta nel riepilogo prima del pagamento.
    </p>
</aside>
