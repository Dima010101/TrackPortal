<?php


/**
 * VPrenotazione - view delle prenotazioni pilota (lista, dettaglio, wizard nuova).
 */
class VPrenotazione extends VBase
{
    public static function lista(array $rows, string $filtro, string $q): void
    {
        self::render('prenotazioni/lista.tpl', 'Le mie prenotazioni', null, [
            'rows'   => $rows,
            'filtro' => $filtro,
            'q'      => $q,
        ]);
    }

    
    public static function dettaglio(
        array $pren,
        bool $confermaCancellazione = false,
        array $fatture = [],
        int $rimborsoPerc = 70,
        float $rimborsoStimato = 0.0
    ): void {
        $attiva = ($pren['stato'] ?? '') === 'confermata'
            && strtotime((string) ($pren['fine_sessione'] ?? '')) >= time();

        self::render('prenotazioni/dettaglio.tpl', 'Prenotazione #' . $pren['id'], null, [
            'pren'                   => $pren,
            'conferma_cancellazione' => $confermaCancellazione,
            'attiva'                 => $attiva,
            'fatture'                => $fatture,
            'rimborso_perc'          => $rimborsoPerc,
            'rimborso_stimato'       => $rimborsoStimato,
        ]);
    }

    
    public static function formModifica(
        array $pren,
        array $form,
        array $errors = [],
        float $prezzoAssicurazione = 0.0
    ): void {
        self::render('prenotazioni/modifica.tpl', 'Modifica prenotazione', null, [
            'pren'                => $pren,
            'form'                => $form,
            'errors'              => $errors,
            'prezzo_assicurazione' => $prezzoAssicurazione,
        ]);
    }

    
    public static function confermaModifica(bool $success, array $pren, array $errors = []): void
    {
        self::render('prenotazioni/modifica_conferma.tpl', 'Esito modifica', null, [
            'success' => $success,
            'pren'    => $pren,
            'errors'  => $errors,
        ]);
    }

   
    public static function formCancellazione(
        array $pren,
        float $rimborso,
        array $errors = [],
        string $causa = '',
        int $rimborsoPerc = 70
    ): void {
        self::render('prenotazioni/cancellazione.tpl', 'Annulla prenotazione', null, [
            'pren'          => $pren,
            'rimborso'      => $rimborso,
            'rimborso_perc' => $rimborsoPerc,
            'errors'        => $errors,
            'causa'         => $causa,
        ]);
    }

    
    public static function confermaCancellazione(
        bool $success,
        array $pren,
        float $rimborso,
        array $errors = [],
        int $rimborsoPerc = 70
    ): void {
        self::render('prenotazioni/cancellazione_conferma.tpl', 'Esito cancellazione', null, [
            'success'       => $success,
            'pren'          => $pren,
            'rimborso'      => $rimborso,
            'rimborso_perc' => $rimborsoPerc,
            'errors'        => $errors,
        ]);
    }

    
    public static function dettaglioSessione(
        ?ESessione $sessione,
        ?array $circuito,
        EPilota $pilota,
        int $postiOccupati,
        array $veicoliNoleggio,
        array $errors = []
    ): void {
        self::render('prenotazioni/dettaglio_sessione.tpl', 'Prenotazione: sessione', null, [
            'sessione'          => self::sessioneToView($sessione),
            'circuito'          => $circuito,
            'pilota'            => self::pilotaToView($pilota),
            'posti_occupati'    => $postiOccupati,
            'veicoli_noleggio'  => $veicoliNoleggio,
            'errors'            => $errors,
        ]);
    }

    
    public static function formDatiUtente(
        ?ESessione $sessione,
        ?array $circuito,
        ?EPilota $pilota,
        array $state,
        array $errors = [],
        float $prezzoAssicurazione = 0.0
    ): void {
        self::render('prenotazioni/conferma_sessione.tpl', 'Prenotazione: veicolo', null, [
            'sessione'             => self::sessioneToView($sessione),
            'circuito'             => $circuito,
            'pilota'               => self::pilotaToView($pilota),
            'state'                => $state,
            'errors'               => $errors,
            'prezzo_assicurazione' => $prezzoAssicurazione,
        ]);
    }

    
    public static function elencoVeicoliNoleggio(
        ?ESessione $sessione,
        ?array $circuito,
        array $veicoli,
        array $errors = [],
        array $state = [],
        float $prezzoAssicurazione = 0.0
    ): void {
        self::render('prenotazioni/veicoli_noleggio.tpl', 'Prenotazione: veicolo a noleggio', null, [
            'sessione'             => self::sessioneToView($sessione),
            'circuito'             => $circuito,
            'veicoli'              => $veicoli,
            'errors'               => $errors,
            'state'                => $state,
            'prezzo_assicurazione' => $prezzoAssicurazione,
        ]);
    }

    
    public static function riepilogoAssicurazione(
        ?ESessione $sessione,
        ?array $circuito,
        array $state,
        float $prezzoAssicurazione,
        float $totale,
        ?array $veicoloRiep = null,
        ?EPromozione $promozione = null,
        float $scontoPromozione = 0.0,
        array $errors = [],
        array $carteSalvate = []
    ): void {
        // La View adatta l'oggetto di dominio alla presentazione: al template
        // passa solo i campi mostrati, non l'entità (separazione View/Model).
        $promozioneView = $promozione !== null
            ? ['titolo' => $promozione->getTitolo(), 'descrizione' => $promozione->getDescrizione()]
            : null;

        self::render('prenotazioni/riepilogo.tpl', 'Prenotazione: riepilogo', null, [
            'sessione'             => self::sessioneToView($sessione),
            'circuito'             => $circuito,
            'state'                => $state,
            'prezzo_assicurazione' => $prezzoAssicurazione,
            'totale'               => $totale,
            'veicolo_riep'         => $veicoloRiep,
            'promozione'           => $promozioneView,
            'sconto_promozione'    => $scontoPromozione,
            'errors'               => $errors,
            'carte_salvate'        => $carteSalvate,
        ]);
    }

    
    public static function confermaFinale(
        array $pren,
        ?array $circuito,
        ?ESessione $sessione
    ): void {
        self::render('prenotazioni/conferma_finale.tpl', 'Prenotazione confermata', null, [
            'pren'     => $pren,
            'circuito' => $circuito,
            'sessione' => self::sessioneToView($sessione),
        ]);
    }

    /**
     * Adatta l'entità sessione ai campi usati dai template: la View traduce il Model in dati di presentazione, così i
     * template restano invariati e l'entità non finisce nel layer di vista.
     */
    private static function sessioneToView(?ESessione $s): ?array
    {
        if ($s === null) {
            return null;
        }

        return [
            'id'                 => $s->getId(),
            'circuito_id'        => $s->getCircuitoId(),
            'inizio'             => $s->getInizio(),
            'fine'               => $s->getFine(),
            'tariffa_accesso'    => $s->getTariffaAccesso(),
            'posti_max'          => $s->getPostiMax(),
            'posti_per_box'      => $s->getPostiPerBox(),
            'note'               => $s->getNote(),
            'stato'              => $s->getStato(),
            'causa_annullamento' => $s->getCausaAnnullamento(),
            'data_creazione'     => $s->getDataCreazione(),
        ];
    }

    /**
     * Adatta l'entità pilota ai campi usati dai template, riproducendo la stessa
     * forma della vecchia riga tramite la mappatura entità→riga della Foundation
     * (così i template restano invariati e il Model non finisce nella vista).
     */
    private static function pilotaToView(?EPilota $p): ?array
    {
        return $p !== null ? FPersistentManager::entityToRow($p) : null;
    }
}
