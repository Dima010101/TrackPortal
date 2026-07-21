<?php

/**
 * Controller del caso d'uso «Prenotazione sessione» (attore: pilota).
 *
 * Un metodo pubblico per ogni passo del flusso (vedi ROUTES, i tag 1a/2a/…
 * sono sui singoli metodi); tassiCambio è un supporto JSON per i prezzi in valuta.
 */
class CPrenotazioneSessione
{
    /** Tabella rotte «pulite» (URL → metodo); risolta da CFrontController. */
    public const ROUTES = [
        'GET {id}' => 'selezionaSessione',
        'POST conferma' => 'confermaSessione',
        'POST targa' => 'inserisciTarga',
        'GET noleggio' => 'richiediVeicoloNoleggio',
        'POST veicolo' => 'selezionaVeicolo',
        'POST pagamento' => 'confermaPagamento',
    ];

    private const SESSION_KEY = 'booking';

    /**
     * GET /api/valute — tassi di cambio (JSON, base EUR). La CSP consente solo
     * 'self', quindi il browser non può chiamare il web service esterno: questo
     * endpoint fa da proxy tramite la Foundation (con cache).
     */
    public function tassiCambio(): void
    {
        json_print(FPersistentManager::cambioValutaTassi());
    }

    /** GET — dettaglio sessione selezionata (1a/1b). */
    public function selezionaSessione(int|string $sessioneId = 0): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user   = CAuth::utenteCorrente();
        $pilota = self::pilotaProntoOppureRedirect((int) $user['id']);
        $id     = (int) ($sessioneId ?: get('sessione_id', '0'));

        [$sessione, $circuito, $errors] = self::caricaSessioneCircuito($id, (int) $user['id']);
        $postiOccupati = 0;
        if ($errors === []) {
            $postiOccupati = self::postiOccupati($sessione);
            self::inizializzaStato($id, $sessione);
        }

        VPrenotazione::dettaglioSessione(
            $sessione,
            $circuito,
            $pilota,
            $postiOccupati,
            self::veicoliDisponibili($errors === [] ? $sessione : null),
            $errors
        );
    }

    /** POST — form dati utente e scelta modalità: veicolo proprio o noleggio (2a). */
    public function confermaSessione(): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user   = CAuth::utenteCorrente();
        $pilota = self::pilotaOppureRedirect((int) $user['id']);
        $state  = $_SESSION[self::SESSION_KEY] ?? null;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::redirectFuoriFlusso($state);
        }

        [$sessione, $circuito, $errors] = self::caricaSessioneCircuito((int) ($state['sessione_id'] ?? 0), (int) $user['id']);
        if ($errors === []) {
            $errors = self::elaboraSceltaModalita($state);
        }
        if ($errors === []) {
            self::mostraFormDatiUtente($sessione, $circuito, $pilota, $_SESSION[self::SESSION_KEY], []);
            return;
        }

        self::mostraDettaglioConErrori($sessione, $circuito, $pilota, $errors);
    }

    /** POST — ramo «veicolo proprio»: elabora targa e mostra riepilogo con assicurazione (3a/3b). */
    public function inserisciTarga(string $targa = ''): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user  = CAuth::utenteCorrente();
        $state = $_SESSION[self::SESSION_KEY] ?? null;
        $targa = strtoupper(trim($targa !== '' ? $targa : (string) post('targa', '')));

        [$sessione, $circuito, $errors] = self::contestoModalita($state, 'proprio', (int) $user['id'],
            'Percorso prenotazione non valido. Seleziona prima una sessione con veicolo proprio.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
            $errore = self::validaTarga($targa);
            if ($errore === null) {
                self::salvaSceltaVeicolo($targa, 0);
                self::renderRiepilogo((int) $user['id'], $sessione, $circuito, $_SESSION[self::SESSION_KEY], []);
                return;
            }
            $errors[] = $errore;
        }

        self::mostraFormDatiUtente($sessione, $circuito, FPersistentManager::pilotaLoadEntityByUtente((int) $user['id']), $state, $errors);
    }

    /** GET — ramo «noleggio» step 1: elenco veicoli disponibili (3a). */
    public function richiediVeicoloNoleggio(): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user  = CAuth::utenteCorrente();
        $state = $_SESSION[self::SESSION_KEY] ?? null;

        [$sessione, $circuito, $errors] = self::contestoModalita($state, 'noleggio', (int) $user['id'],
            'Percorso prenotazione non valido. Conferma prima una sessione con veicolo a noleggio.');

        $veicoli = $errors === [] ? self::veicoliDisponibili($sessione) : [];
        if ($errors === [] && $veicoli === []) {
            $errors[] = 'Nessun veicolo a noleggio disponibile per questo circuito nella fascia oraria della sessione.';
        }

        self::mostraElencoNoleggio($sessione, $circuito, $veicoli, $errors, $state);
    }

    /** POST — ramo «noleggio» step 2: associa veicolo e mostra riepilogo con assicurazione (3b). */
    public function selezionaVeicolo(int|string $idVeicolo = 0): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user      = CAuth::utenteCorrente();
        $state     = $_SESSION[self::SESSION_KEY] ?? null;
        $veicoloId = (int) ($idVeicolo ?: post('veicolo_id', 0));

        [$sessione, $circuito, $errors] = self::contestoModalita($state, 'noleggio', (int) $user['id'], 'Percorso prenotazione non valido.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
            $errore = self::validaVeicoloScelto($veicoloId, $state, $circuito, (int) $user['id']);
            if ($errore === null) {
                self::salvaSceltaVeicolo('', $veicoloId);
                self::renderRiepilogo((int) $user['id'], $sessione, $circuito, $_SESSION[self::SESSION_KEY], []);
                return;
            }
            $errors[] = $errore;
        }

        self::mostraElencoNoleggio($sessione, $circuito, self::veicoliDisponibiliState($state ?? []), $errors, $state);
    }

    /** POST — elabora pagamento, salva prenotazione e mostra conferma finale (4a/4b). */
    public function confermaPagamento(array $datiPagamento = [], int|string|null $assicurazione = null): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $user = CAuth::utenteCorrente();

        $datiPagamento = $datiPagamento !== [] ? $datiPagamento : self::datiPagamentoDaPost();
        $_SESSION[self::SESSION_KEY]['assicurazione'] = (int) ($assicurazione ?? (post('assicurazione') ? 1 : 0));
        $state = $_SESSION[self::SESSION_KEY] ?? null;

        [$sessione, $circuito, $errors] = self::contestoPagamento($state, (int) $user['id']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
            $errors = self::processaPagamento($user, $sessione, $circuito, $datiPagamento);
            if ($errors === []) {
                return; // conferma finale già mostrata
            }
        }

        if ($errors !== [] && $sessione && $circuito) {
            self::renderRiepilogo((int) $user['id'], $sessione, $circuito, $state, $errors);
            return;
        }

        flash('error', $errors[0] ?? 'Prenotazione non disponibile.');
        redirect('/dashboard');
    }

    // ---- Guard e contesto del flusso (helper privati) ----

    /** Profilo pilota dell'utente loggato, o redirect all'account. */
    private static function pilotaOppureRedirect(int $utenteId): EPilota
    {
        $pilota = FPersistentManager::pilotaLoadEntityByUtente($utenteId);
        if (!$pilota) {
            flash('error', 'Profilo pilota non configurato. Completa il tuo account.');
            redirect('/account');
        }

        return $pilota;
    }

    /**
     * Pre-condizioni del UC (documenti approvati, licenza valida): se violate,
     * redirect all'account.
     */
    private static function pilotaProntoOppureRedirect(int $utenteId): EPilota
    {
        $pilota = self::pilotaOppureRedirect($utenteId);
        foreach ([$pilota->erroreDocumenti(), $pilota->erroreLicenza()] as $errore) {
            if ($errore !== null) {
                flash('warn', $errore);
                redirect('/account');
            }
        }

        return $pilota;
    }

    /**
     * Carica sessione + circuito e verifica la prenotabilità.
     */
    private static function caricaSessioneCircuito(int $sessioneId, int $pilotaId): array
    {
        $sessione = $sessioneId > 0 ? FPersistentManager::sessioneLoadById($sessioneId) : null;
        if (!$sessione) {
            return [null, null, [$sessioneId > 0 ? 'Sessione non più disponibile.' : 'Sessione non trovata.']];
        }

        $circuito = FPersistentManager::circuitoLoadById($sessione->getCircuitoId());
        if (!$circuito) {
            return [$sessione, null, ['Circuito associato alla sessione non trovato.']];
        }

        $errDisp = self::erroreDisponibilitaSessione($sessione, $pilotaId);

        return [$sessione, $circuito, $errDisp !== null ? [$errDisp] : []];
    }

    /**
     * Contesto degli step dopo la scelta modalità: lo stato in sessione deve
     * esistere ed essere nel ramo atteso (proprio/noleggio).
     */
    private static function contestoModalita(?array $state, string $modalita, int $pilotaId, string $msgFuoriFlusso): array
    {
        if ($state === null || ($state['modalita'] ?? '') !== $modalita) {
            return [null, null, [$msgFuoriFlusso]];
        }

        return self::caricaSessioneCircuito((int) $state['sessione_id'], $pilotaId);
    }

    /**
     * Contesto del pagamento: oltre alla sessione servono i dati del veicolo
     * del ramo corrente (targa o veicolo a noleggio).
     */
    private static function contestoPagamento(?array $state, int $pilotaId): array
    {
        if ($state === null || empty($state['sessione_id'])) {
            return [null, null, ['Nessuna prenotazione in corso.']];
        }

        [$sessione, $circuito, $errors] = self::caricaSessioneCircuito((int) $state['sessione_id'], $pilotaId);
        $modalita = (string) ($state['modalita'] ?? '');
        if ($modalita === 'proprio' && empty($state['targa'])) {
            $errors[] = 'Targa veicolo mancante.';
        }
        if ($modalita === 'noleggio' && empty($state['veicolo_id'])) {
            $errors[] = 'Veicolo a noleggio non selezionato.';
        }

        return [$sessione, $circuito, $errors];
    }

    /** GET fuori flusso su un'operazione POST: torna al punto giusto del flusso. */
    private static function redirectFuoriFlusso(?array $state): never
    {
        if (!empty($state['sessione_id'])) {
            redirect('/prenotazione/' . (int) $state['sessione_id']);
        }

        flash('error', 'Seleziona una sessione dal calendario.');
        redirect('/dashboard');
    }

    /** Inizializza lo stato del flusso di prenotazione in sessione. */
    private static function inizializzaStato(int $sessioneId, ESessione $sessione): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'sessione_id'     => $sessioneId,
            'circuito_id'     => $sessione->getCircuitoId(),
            'inizio_sessione' => $sessione->getInizio(),
            'fine_sessione'   => $sessione->getFine(),
            'modalita'        => 'proprio',
            'targa'           => '',
            'veicolo_id'      => 0,
            'assicurazione'   => 0,
        ];
    }

    /** Registra nello stato il veicolo scelto (targa propria o id noleggio). */
    private static function salvaSceltaVeicolo(string $targa, int $veicoloId): void
    {
        $_SESSION[self::SESSION_KEY]['targa']         = $targa;
        $_SESSION[self::SESSION_KEY]['veicolo_id']    = $veicoloId;
        $_SESSION[self::SESSION_KEY]['assicurazione'] = post('assicurazione') ? 1 : 0;
    }

    /**
     * Valida la scelta della modalità (CSRF compreso), la salva nello stato e
     * instrada il ramo noleggio; ritorna gli errori per il ramo proprio.
     */
    private static function elaboraSceltaModalita(?array $state): array
    {
        if (!csrf_check(post('csrf_token'))) {
            return ['Token CSRF non valido.'];
        }

        $modalita = (string) post('modalita', 'proprio');
        if (!in_array($modalita, ['proprio', 'noleggio'], true)) {
            return ['Modalità di partecipazione non valida.'];
        }
        if ($modalita === 'noleggio' && self::veicoliDisponibiliState($state ?? []) === []) {
            return ['Nessun veicolo a noleggio disponibile per questo circuito nella fascia oraria della sessione.'];
        }

        $_SESSION[self::SESSION_KEY]['modalita'] = $modalita;
        if ($modalita === 'noleggio') {
            redirect('/prenotazione/noleggio');
        }

        return [];
    }

    /** Form dati utente (3a ramo «proprio»/2a) con prezzo assicurazione corrente. */
    private static function mostraFormDatiUtente(?ESessione $sessione, ?array $circuito, ?EPilota $pilota, ?array $state, array $errors): void
    {
        VPrenotazione::formDatiUtente(
            $sessione,
            $circuito,
            $pilota,
            $state ?? [],
            $errors,
            FPersistentManager::configurazionePiattaformaPrezzoAssicurazione()
        );
    }

    /** Elenco veicoli a noleggio (3a/3b ramo «noleggio») con prezzo assicurazione. */
    private static function mostraElencoNoleggio(?ESessione $sessione, ?array $circuito, array $veicoli, array $errors, ?array $state): void
    {
        VPrenotazione::elencoVeicoliNoleggio(
            $sessione,
            $circuito,
            $veicoli,
            $errors,
            $state ?? [],
            FPersistentManager::configurazionePiattaformaPrezzoAssicurazione()
        );
    }

    /** Ripropone il dettaglio sessione (1b) con gli errori del passo corrente. */
    private static function mostraDettaglioConErrori(?ESessione $sessione, ?array $circuito, ?EPilota $pilota, array $errors): void
    {
        VPrenotazione::dettaglioSessione(
            $sessione,
            $circuito,
            $pilota,
            $sessione !== null ? self::postiOccupati($sessione) : 0,
            self::veicoliDisponibili($circuito !== null ? $sessione : null),
            $errors
        );
    }

    private static function postiOccupati(ESessione $sessione): int
    {
        return FPersistentManager::sessioneCountPrenotazioniAttive((int) $sessione->getId());
    }

    /** Veicoli a noleggio liberi per la sessione. */
    private static function veicoliDisponibili(?ESessione $sessione): array
    {
        if ($sessione === null) {
            return [];
        }

        return FPersistentManager::veicoloNoleggioLoadDisponibiliByCircuito(
            $sessione->getCircuitoId(),
            (int) $sessione->getId()
        );
    }

    private static function veicoliDisponibiliState(array $state): array
    {
        return FPersistentManager::veicoloNoleggioLoadDisponibiliByCircuito(
            (int) ($state['circuito_id'] ?? 0),
            (int) ($state['sessione_id'] ?? 0)
        );
    }

    // ---- Validazioni di passo ----

    private static function validaTarga(string $targa): ?string
    {
        if (!csrf_check(post('csrf_token'))) {
            return 'Token CSRF non valido.';
        }
        if ($targa === '') {
            return 'Inserisci la targa del tuo veicolo.';
        }
        if (!targa_valida($targa)) {
            return 'Targa non valida: usa il formato italiano (auto AA000AA o moto AA00000).';
        }

        return null;
    }

    /**
     * Verifica il veicolo scelto nel ramo noleggio (esistenza, disponibilità,
     * circuito) e i vincoli di noleggio.
     */
    private static function validaVeicoloScelto(int $veicoloId, ?array $state, ?array $circuito, int $pilotaId): ?string
    {
        if (!csrf_check(post('csrf_token'))) {
            return 'Token CSRF non valido.';
        }
        if ($veicoloId < 1) {
            return 'Seleziona un veicolo da noleggiare.';
        }

        $veicolo = FPersistentManager::veicoloNoleggioLoadById($veicoloId);
        if (!$veicolo
            || (int) $veicolo['circuito_id'] !== (int) ($state['circuito_id'] ?? 0)
            || empty($veicolo['disponibile'])) {
            return 'Il veicolo selezionato non è più disponibile.';
        }

        return self::erroreVincoliNoleggio($veicolo, $state ?? [], $circuito, $pilotaId);
    }

    /**
     * Vincoli del noleggio: sanzioni dell'azienda, sovrapposizioni con altre
     * prenotazioni, categoria ammessa dal circuito.
     */
    private static function erroreVincoliNoleggio(array $veicolo, array $state, ?array $circuito, int $pilotaId): ?string
    {
        // Sanzione dell'azienda proprietaria: blocca il noleggio dei suoi
        // mezzi, il pilota resta libero altrove.
        $sanzione = FPersistentManager::sanzionePilotaNoleggioPilotaBloccatoSuAzienda($pilotaId, (int) $veicolo['azienda_id']);
        if ($sanzione !== null) {
            return self::messaggioSanzione($sanzione, true);
        }

        if (FPersistentManager::veicoloNoleggioPrenotatoPerSessione(
            (int) $veicolo['id'],
            (int) ($state['sessione_id'] ?? 0)
        )) {
            return 'Il veicolo selezionato è già prenotato per questa sessione.';
        }

        $vcat = (string) ($veicolo['categoria'] ?? '');
        if ($circuito && !ECircuito::ammetteCategoria((string) $circuito['tipologia_veicoli'], $vcat)) {
            return $vcat === 'moto' ? 'Questo circuito non ammette moto.' : 'Questo circuito non ammette auto.';
        }

        return null;
    }

    /**
     * Controlli di prenotabilità: il controller raccoglie i fatti dalla
     * Foundation e lascia le decisioni di dominio a ESessione.
     */
    private static function erroreDisponibilitaSessione(ESessione $sessione, int $pilotaId): ?string
    {
        if ($sessione->isAnnullata()) {
            return 'La sessione è stata annullata e non accetta prenotazioni.';
        }

        // Sanzione del gestore di QUESTO circuito: blocca i suoi circuiti,
        // il pilota resta libero altrove.
        $sanzione = FPersistentManager::sanzionePilotaPilotaBloccatoSuCircuito($pilotaId, $sessione->getCircuitoId());
        if ($sanzione !== null) {
            return self::messaggioSanzione($sanzione);
        }

        // 'privata' (o altro stato non prenotabile) non ammette prenotazioni.
        if ($sessione->categoriaAmmessa() === null) {
            return 'La sessione è privata e non accetta prenotazioni.';
        }

        return self::errorePilotaAmmesso($sessione, $pilotaId) ?? self::erroreStatoSessione($sessione, $pilotaId);
    }

    /** Licenza valida e categoria del pilota compatibile con la sessione. */
    private static function errorePilotaAmmesso(ESessione $sessione, int $pilotaId): ?string
    {
        $pilota = FPersistentManager::pilotaLoadEntityByUtente($pilotaId);

        $licErr = $pilota?->erroreLicenza();
        if ($licErr !== null) {
            return $licErr;
        }

        if (!$sessione->ammettePilota($pilota?->getCategoria() ?? '')) {
            $etichetta = $sessione->getStato() === 'professionistica' ? 'piloti professionisti' : 'piloti amatoriali';

            return 'Questa sessione è riservata ai ' . $etichetta . '.';
        }

        return null;
    }

    /** Sessione non scaduta, senza doppia prenotazione e con posti liberi. */
    private static function erroreStatoSessione(ESessione $sessione, int $pilotaId): ?string
    {
        try {
            $scaduta = $sessione->isScaduta(new DateTimeImmutable('now'));
        } catch (Exception) {
            return 'Sessione non valida.';
        }
        if ($scaduta) {
            return 'La sessione è scaduta e non può più essere prenotata.';
        }

        if (FPersistentManager::sessionePilotaHaPrenotazioneAttiva($pilotaId, (int) $sessione->getId())) {
            return 'Hai già una prenotazione attiva per questa sessione.';
        }

        return $sessione->isAlCompleto(self::postiOccupati($sessione))
            ? 'Sessione al completo: tutti i posti sono già occupati.'
            : null;
    }

    /**
     * Messaggio di blocco per un pilota sanzionato (dal gestore del circuito
     * o, con $noleggio, dall'azienda del veicolo).
     */
    private static function messaggioSanzione(array $sanzione, bool $noleggio = false): string
    {
        $motivo = trim((string) ($sanzione['motivo'] ?? ''));
        $coda   = $motivo !== '' ? ' Motivo: ' . $motivo : '';
        $chi    = $noleggio ? "L'azienda di noleggio di questo veicolo ti ha" : 'Il gestore di questo circuito ti ha';
        $cosa   = $noleggio ? 'noleggiare i suoi mezzi' : 'prenotare le sessioni dei suoi circuiti';

        if ((string) ($sanzione['tipo'] ?? '') === ESanzionePilota::TIPO_BAN) {
            return $chi . ' bannato: non puoi ' . $cosa . '.' . $coda;
        }

        return $chi . ' sospeso' . self::finoAl($sanzione) . ': non puoi ' . $cosa . '.' . $coda;
    }

    private static function finoAl(array $sanzione): string
    {
        try {
            return ' fino al ' . (new DateTimeImmutable((string) ($sanzione['data_fine'] ?? '')))->format('d/m/Y');
        } catch (Exception) {
            return '';
        }
    }

    // ---- Riepilogo e pagamento ----

    private static function renderRiepilogo(int $pilotaId, ?ESessione $sessione, ?array $circuito, array $state, array $errors): void
    {
        $prezzoAssicurazione = FPersistentManager::configurazionePiattaformaPrezzoAssicurazione();
        $veicolo = (($state['modalita'] ?? '') === 'noleggio' && !empty($state['veicolo_id']))
            ? FPersistentManager::veicoloNoleggioLoadById((int) $state['veicolo_id'])
            : null;

        // La tariffa di accesso è definita sulla SESSIONE (non sul circuito).
        $base      = ($sessione !== null ? $sessione->getTariffaAccesso() : 0.0) + (float) ($veicolo['prezzo'] ?? 0);
        $promoInfo = self::trovaPromozioneAutomatica(
            $pilotaId,
            (int) ($state['circuito_id'] ?? 0),
            !empty($state['veicolo_id']) ? (int) $state['veicolo_id'] : null,
            $base
        );
        $sconto = (float) ($promoInfo['sconto'] ?? 0.0);
        $totale = EPrenotazione::calcolaPrezzoTotale($base, $sconto, !empty($state['assicurazione']), $prezzoAssicurazione);

        VPrenotazione::riepilogoAssicurazione(
            $sessione, $circuito, $state, $prezzoAssicurazione, $totale, $veicolo,
            $promoInfo['promozione'] ?? null, $sconto, $errors,
            FPersistentManager::cartaCreditoLoadByPilota($pilotaId)
        );
    }

    private static function datiPagamentoDaPost(): array
    {
        return [
            'carta_salvata_id' => (int) post('carta_salvata_id', 0),
            'salva_carta'      => post('salva_carta') ? 1 : 0,
            'cc_nome'    => (string) post('cc_nome', ''),
            'cc_cognome' => (string) post('cc_cognome', ''),
            'cc_numero'  => (string) post('cc_numero', ''),
            'cc_scad'    => (string) post('cc_scad', ''),
            'cc_cvv'     => (string) post('cc_cvv', ''),
        ];
    }

    /** Valida CSRF, gate documenti (anche contro POST diretti) e dati di pagamento. */
    private static function erroreValidazionePagamento(array $dati, int $pilotaId): ?string
    {
        if (!csrf_check(post('csrf_token'))) {
            return 'Token CSRF non valido.';
        }

        $pilota = FPersistentManager::pilotaLoadEntityByUtente($pilotaId);
        $docErr = $pilota !== null ? $pilota->erroreDocumenti() : 'Profilo pilota non configurato.';
        if ($docErr !== null) {
            return $docErr;
        }

        return self::validaDatiPagamento($dati, $pilotaId);
    }

    /**
     * Validazione lato server dei dati di pagamento: carta salvata di proprietà
     * del pilota oppure campi della nuova carta.
     */
    private static function validaDatiPagamento(array $dati, int $pilotaId): ?string
    {
        $cartaSalvataId = (int) ($dati['carta_salvata_id'] ?? 0);
        if ($cartaSalvataId > 0) {
            return FPersistentManager::cartaCreditoFindOwned($cartaSalvataId, $pilotaId) !== null
                ? null
                : 'La carta selezionata non è più disponibile.';
        }

        return self::erroreNuovaCarta($dati);
    }

    private static function erroreNuovaCarta(array $dati): ?string
    {
        if (!cc_titolare_valido((string) ($dati['cc_nome'] ?? ''))) {
            return 'Nome del titolare non valido (solo lettere, minimo 2 caratteri).';
        }
        if (!cc_titolare_valido((string) ($dati['cc_cognome'] ?? ''))) {
            return 'Cognome del titolare non valido (solo lettere, minimo 2 caratteri).';
        }
        if (!cc_numero_valido((string) ($dati['cc_numero'] ?? ''))) {
            return 'Numero della carta non valido: controlla le cifre inserite.';
        }
        if (!cc_scadenza_valida((string) ($dati['cc_scad'] ?? ''))) {
            return 'Scadenza non valida o carta scaduta (formato MM/AAAA).';
        }
        if (!cc_cvv_valido((string) ($dati['cc_cvv'] ?? ''))) {
            return 'CVV non valido: inserisci 3 o 4 cifre.';
        }

        return null;
    }

    /**
     * Valida il pagamento, registra la prenotazione in transazione, emette i
     * documenti fiscali e mostra la conferma. Ritorna gli errori (vuoto se la
     * conferma è già stata resa).
     */
    private static function processaPagamento(array $user, ?ESessione $sessione, ?array $circuito, array $datiPagamento): array
    {
        $errore = self::erroreValidazionePagamento($datiPagamento, (int) $user['id']);
        if ($errore !== null) {
            return [$errore];
        }

        $state = $_SESSION[self::SESSION_KEY];
        try {
            $codice = self::salvaPrenotazione($user, $circuito, $state, $datiPagamento);
        } catch (Throwable $ex) {
            return ['Errore durante la prenotazione: ' . $ex->getMessage()];
        }

        $pren = FPersistentManager::prenotazioneLoadDettaglioByCodice($codice, (int) $user['id']);
        if ($pren !== null) {
            self::emettiDocumentiENotifiche((int) $pren['id'], (int) $user['id']);
        }
        unset($_SESSION[self::SESSION_KEY]);
        VPrenotazione::confermaFinale($pren ?? ['codice_identificativo' => $codice], $circuito, $sessione);

        return [];
    }

    /**
     * Documenti fiscali + email con ricevuta: best-effort, un errore qui non
     * invalida la prenotazione già confermata.
     */
    private static function emettiDocumentiENotifiche(int $prenotazioneId, int $utenteId): void
    {
        try {
            FPersistentManager::fatturazioneEmettiPerPrenotazione($prenotazioneId);
        } catch (Throwable $e) {
            error_log('Emissione documenti fiscali fallita: ' . $e->getMessage());
        }

        try {
            FPersistentManager::notifichePrenotazioneCompletata($prenotazioneId, $utenteId);
        } catch (Throwable $e) {
            error_log('Notifiche prenotazione: ' . $e->getMessage());
        }
    }

    /** Registra la prenotazione in transazione e ritorna il codice identificativo. */
    private static function salvaPrenotazione(array $user, array $circuito, array $state, array $datiPagamento): string
    {
        return FPersistentManager::transaction(function () use ($user, $circuito, $state, $datiPagamento): string {
            $sessione = self::sessioneBloccataPerPrenotazione((int) $state['sessione_id'], (int) $user['id']);

            $boxAssegnato = FPersistentManager::prenotazioneAssegnaBox(
                (int) $sessione->getId(),
                (int) ($circuito['numero_box'] ?? 0),
                $sessione->getPostiPerBox()
            );

            [$prezzoBase, $importoNoleggio] = self::importiBase($sessione, $state, $circuito, (int) $user['id']);
            $promoInfo = self::trovaPromozioneAutomatica(
                (int) $user['id'],
                (int) $state['circuito_id'],
                !empty($state['veicolo_id']) ? (int) $state['veicolo_id'] : null,
                $prezzoBase
            );

            return self::persistiPrenotazione($user, $state, $datiPagamento, $boxAssegnato, $prezzoBase, $importoNoleggio, $promoInfo);
        });
    }

    /**
     * Lock pessimistico sulla sessione: serializza le prenotazioni concorrenti
     * ed evita l'overbooking dell'ultimo posto; la rilettura sotto lock
     * intercetta un annullamento concorrente.
     */
    private static function sessioneBloccataPerPrenotazione(int $sessioneId, int $pilotaId): ESessione
    {
        $sessione = FPersistentManager::findForUpdate(ESessione::class, $sessioneId);
        if (!$sessione instanceof ESessione) {
            throw new RuntimeException('Sessione non più disponibile.');
        }
        FPersistentManager::em()->refresh($sessione);

        $errDisp = self::erroreDisponibilitaSessione($sessione, $pilotaId);
        if ($errDisp !== null) {
            throw new RuntimeException($errDisp);
        }

        return $sessione;
    }

    /**
     * Importi base (tariffa della sessione letta sotto lock + eventuale
     * noleggio): [prezzo base, importo noleggio].
     */
    private static function importiBase(ESessione $sessione, array $state, array $circuito, int $pilotaId): array
    {
        $prezzoBase = $sessione->getTariffaAccesso();
        if (($state['modalita'] ?? '') !== 'noleggio' || empty($state['veicolo_id'])) {
            return [$prezzoBase, 0.0];
        }

        $veicolo = self::veicoloBloccatoPerNoleggio((int) $state['veicolo_id'], $state, $circuito, $pilotaId);

        return [$prezzoBase + $veicolo->getPrezzo(), $veicolo->getPrezzo()];
    }

    /**
     * Ricontrolla il veicolo sotto lock (circuito, disponibilità, categoria,
     * sanzioni, sovrapposizioni): tra la scelta del veicolo e il saldo può
     * cambiare tutto.
     */
    private static function veicoloBloccatoPerNoleggio(int $veicoloId, array $state, array $circuito, int $pilotaId): EVeicoloNoleggio
    {
        $veicolo = FPersistentManager::findForUpdate(EVeicoloNoleggio::class, $veicoloId);
        if (!$veicolo instanceof EVeicoloNoleggio
            || $veicolo->getCircuitoId() !== (int) $state['circuito_id']
            || !$veicolo->isDisponibile()) {
            throw new RuntimeException('Veicolo non più disponibile.');
        }
        if (!ECircuito::ammetteCategoria((string) $circuito['tipologia_veicoli'], $veicolo->getCategoria())) {
            throw new RuntimeException(
                $veicolo->getCategoria() === 'moto' ? 'Questo circuito non ammette moto.' : 'Questo circuito non ammette auto.'
            );
        }

        $sanzione = FPersistentManager::sanzionePilotaNoleggioPilotaBloccatoSuAzienda($pilotaId, $veicolo->getAziendaId());
        if ($sanzione !== null) {
            throw new RuntimeException(self::messaggioSanzione($sanzione, true));
        }
        if (FPersistentManager::veicoloNoleggioPrenotatoPerSessione($veicoloId, (int) ($state['sessione_id'] ?? 0))) {
            throw new RuntimeException('Veicolo già prenotato per questa sessione.');
        }

        return $veicolo;
    }

    /**
     * Riusa una carta salvata (verificandone la proprietà) o memorizza la nuova
     * solo se richiesto. Il CVV viene validato ma MAI memorizzato (PCI-DSS).
     */
    private static function risolviCartaCredito(array $datiPagamento, int $pilotaId): ?int
    {
        $cartaSalvataId = (int) ($datiPagamento['carta_salvata_id'] ?? 0);
        if ($cartaSalvataId > 0) {
            $salvata = FPersistentManager::cartaCreditoFindOwned($cartaSalvataId, $pilotaId);
            if ($salvata === null) {
                throw new RuntimeException('La carta selezionata non è più disponibile.');
            }

            return (int) $salvata->getId();
        }
        if (empty($datiPagamento['salva_carta'])) {
            return null;
        }

        $numero = preg_replace('/\D/', '', (string) $datiPagamento['cc_numero']);

        return FPersistentManager::cartaCreditoSalva(
            $pilotaId,
            (string) $datiPagamento['cc_nome'],
            (string) $datiPagamento['cc_cognome'],
            '**** **** **** ' . substr((string) $numero, -4),
            (string) $datiPagamento['cc_scad']
        );
    }

    /**
     * Costruisce e salva la prenotazione («confermata»), con lo scorporo degli
     * imponibili delegato al Model; ritorna il codice.
     */
    private static function persistiPrenotazione(array $user, array $state, array $datiPagamento, int $boxAssegnato, float $prezzoBase, float $importoNoleggio, array $promoInfo): string
    {
        $prezzoAssicurazione = FPersistentManager::configurazionePiattaformaPrezzoAssicurazione();
        $assicurazione       = !empty($state['assicurazione']);
        $sconto              = (float) ($promoInfo['sconto'] ?? 0.0);
        $prezzo              = EPrenotazione::calcolaPrezzoTotale($prezzoBase, $sconto, $assicurazione, $prezzoAssicurazione);
        $codice              = EPrenotazione::generaCodice();
        $modalita            = (string) ($state['modalita'] ?? '');

        $pren = new EPrenotazione(
            (int) $user['id'], (int) $state['sessione_id'], $boxAssegnato,
            $modalita === 'noleggio' ? (int) $state['veicolo_id'] : null,
            $modalita === 'proprio' ? strtoupper((string) $state['targa']) : null,
            $assicurazione, $prezzo, 'EUR', 'confermata', $codice,
            self::risolviCartaCredito($datiPagamento, (int) $user['id']),
            $promoInfo['promozione']?->getId(), $sconto
        );
        $pren->scomponiImponibili($prezzoBase, $sconto, $importoNoleggio, $prezzoAssicurazione);
        FPersistentManager::store($pren);

        return $codice;
    }

    /**
     * Sceglie la promozione migliore: carica le candidate e delega al Model
     * applicabilità e calcolo dello sconto.
     */
    private static function trovaPromozioneAutomatica(int $pilotaId, int $circuitoId, ?int $veicoloId, float $baseImporto): array
    {
        $adesso = new DateTimeImmutable('now');
        // Lo storico del pilota sul circuito è costante nel ciclo: una sola query.
        $storiche = FPersistentManager::prenotazioneCountStoricheByPilotaCircuito($pilotaId, $circuitoId);

        $migliore      = null;
        $migliorSconto = 0.0;
        foreach (FPersistentManager::promozioneLoadAttivePerPrenotazione($circuitoId, $veicoloId) as $promozione) {
            if (!$promozione->isApplicabile($circuitoId, $veicoloId, $adesso, $baseImporto, $storiche)) {
                continue;
            }
            $sconto = $promozione->calcolaSconto($baseImporto);
            if ($sconto > $migliorSconto) {
                $migliorSconto = $sconto;
                $migliore      = $promozione;
            }
        }

        return ['promozione' => $migliore, 'sconto' => $migliorSconto];
    }
}