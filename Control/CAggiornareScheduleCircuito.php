<?php

/**
 * use case Aggiornare la schedule del circuito (uc 2).
 *
 * Operazioni di sistema:
 *  - visualizzaCalendario()           → 1a/1b: calendario settimanale del circuito
 *  - selezionaSlot()                  → 2a/2b: scheda dello slot (sessione o form vuoto)
 *  - salvaSessione()                  → 3a/3b: crea/aggiorna la sessione e torna al calendario
 *  - annullaSessione()                → 4a: scheda di conferma annullamento con motivazione
 *  - confermaAnnullamentoSessione()   → 4b: annulla e storna al 100% le prenotazioni
 *
 * raccoglie l'input, delega i dati della griglia a Foundation e le decisioni di dominio alle entità; alle View passa solo il risultato.
 */
class CAggiornareScheduleCircuito
{
    /** Azione base invocata dall'URL di sezione (senza azione) */
    public const DEFAULT_ACTION = 'visualizzaCalendario';

    /** Tabella URL → metodo */
    public const ROUTES = [
        'GET slot/{p}/{p}' => 'selezionaSlot',
        'POST sessione' => 'salvaSessione',
        'GET {id}/annulla' => 'annullaSessione',
        'POST annulla' => 'confermaAnnullamentoSessione',
    ];

    private const DURATA_MIN = 1;

    private const DURATA_MAX = 4;

    /**  calendario settimanale dei circuiti del gestore (1a/1b). GET /calendario*/
    public function visualizzaCalendario(): void
    {
        $gestoreId = self::richiediGestoreCircuiti();

        self::renderCalendario(
            $gestoreId,
            (int) get('circuito_id', '0'),
            (int) get('settimana', '0'),
            []
        );
    }

    /** scheda sessione per lo slot selezionato (2a/2b). GET /calendario/slot/{data}/{ora} */
    public function selezionaSlot(string $data = '', string $ora = ''): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) get('circuito_id', '0');

        [$sessione, $errors, $data, $ora] = self::caricaSlot($circuitoId, $gestoreId, $data, $ora);

        VGestoreCircuiti::schedaSessione(
            $circuitoId > 0 ? FPersistentManager::circuitoLoadById($circuitoId) : null,
            $data,
            $ora,
            $sessione,
            $errors,
            (int) get('settimana', '0'),
            [],
            self::prenotazioniIntervallo($sessione),
            self::idsPilotiSanzionabili($gestoreId)
        );
    }

    /**
     * salva i dati della sessione e restituisce il calendario aggiornato (3a/3b). POST /calendario/sessione  */
    public function salvaSessione(array $dati = []): void
    {
        $gestoreId = self::richiediGestoreCircuiti();
        $dati      = $dati !== [] ? $dati : self::datiDaPost();

        $errors = !csrf_check(isset($dati['csrf_token']) ? (string) $dati['csrf_token'] : null)
            ? ['Token CSRF non valido.']
            : self::validaSalvaSessione($dati, $gestoreId);

        if ($errors === []) {
            try {
                self::persistiSessione($dati);
                flash('ok', 'Sessione salvata.');
                self::renderCalendario($gestoreId, (int) $dati['circuito_id'], (int) ($dati['settimana'] ?? 0), []);
                return;
            } catch (Throwable $ex) {
                $errors = ['Errore durante il salvataggio: ' . $ex->getMessage()];
            }
        }

        self::mostraSchedaConErrori($gestoreId, $dati, $errors);
    }

    /** conferma annullamento sessione (4a). GET /calendario/{id}/annulla */
    public function annullaSessione(int|string $sessione = 0): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $sessioneId = (int) ($sessione ?: (get('sessione_id', '0')));
        $settimana  = (int) get('settimana', '0');

        $sessioneRow = self::sessioneAnnullabile($sessioneId, $gestoreId, $settimana);

        VGestoreCircuiti::formAnnullaSessione($sessioneRow, self::pilotiConfermati($sessioneRow), $settimana, [], '');
    }

    /** annulla la sessione con rimborso al 100% ai piloti (4b).POST /calendario/annulla */
    public function confermaAnnullamentoSessione(): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $sessioneId = (int) post('sessione_id', '0');
        $settimana  = (int) post('settimana', '0');
        $causa      = (string) post('causa', '');

        $sessioneRow = $sessioneId > 0 ? FPersistentManager::sessioneLoadByIdForGestore($sessioneId, $gestoreId) : null;
        if ($sessioneRow === null) {
            flash('error', 'Sessione non trovata o non di tua proprietà.');
            redirect('/calendario');
        }

        $errors = self::erroriRichiestaAnnullamento();
        if ($errors === []) {
            $errors = self::eseguiAnnullamento($sessioneId, $gestoreId, $causa, $sessioneRow, $settimana);
        }

        VGestoreCircuiti::formAnnullaSessione($sessioneRow, self::pilotiConfermati($sessioneRow), $settimana, $errors, $causa);
    }

    /**modifica dei dati di una sessione esistente */
    private static function aggiornaSessioneEsistente(int $sessioneId, array $dati, string $inizio, string $fine, float $tariffa, ?string $note, string $stato): void
    {
        $entity = FPersistentManager::sessioneLoadEntityById($sessioneId);
        if (!$entity instanceof ESessione || $entity->getCircuitoId() !== (int) $dati['circuito_id']) {
            throw new RuntimeException('Sessione non trovata.');
        }

        $entity->aggiornaDettagli($inizio, $fine, $tariffa, (int) ($dati['posti_max'] ?? 1), (int) ($dati['posti_per_box'] ?? 1), $note, $stato);
        FPersistentManager::flush();
    }

    /**
     * carica lo slot della sessione richiesto.
     * Lo slot va bloccato solo se è prenotato ma non esiste una sessione da
     * gestire: una sessione con prenotazioni deve restare apribile per poterla
     * modificare o annullare*/
    private static function caricaSlot(int $circuitoId, int $gestoreId, string $data, string $ora): array
    {
        $parsed = self::parseDataOra($data, $ora);
        if ($parsed === null) {
            return [null, ['Data o orario non validi.'], $data, $ora];
        }
        if (!FPersistentManager::circuitoIsDelGestore($circuitoId, $gestoreId)) {
            return [null, ['Circuito non valido o non di tua proprietà.'], $data, $ora];
        }

        [$data, $ora] = $parsed;
        $sessione     = FPersistentManager::sessioneLoadByCircuitoSlot($circuitoId, $data, $ora);
        if ($sessione === null && self::slotBloccato($circuitoId, $data, $ora)) {
            return [null, ['Lo slot selezionato è già occupato da una prenotazione.'], $data, $ora];
        }

        return [$sessione, [], $data, $ora];
    }

    /** Prenotazioni sull'intervallo della sessione (vuoto se lo slot è libero)  */
    private static function prenotazioniIntervallo(?array $sessione): array
    {
        if ($sessione === null) {
            return [];
        }

        return FPersistentManager::prenotazioneLoadBySessioneCircuito(
            (int) $sessione['circuito_id'],
            (string) $sessione['inizio'],
            (string) $sessione['fine']
        );
    }

    /** Ripropone la scheda sessione con i dati inviati e gli errori */
    private static function mostraSchedaConErrori(int $gestoreId, array $dati, array $errors): void
    {
        $circuitoId  = (int) ($dati['circuito_id'] ?? 0);
        $sessioneRow = self::sessioneDelGestore((int) ($dati['sessione_id'] ?? 0), $gestoreId);

        VGestoreCircuiti::schedaSessione(
            $circuitoId > 0 ? FPersistentManager::circuitoLoadById($circuitoId) : null,
            (string) ($dati['data'] ?? ''),
            (string) ($dati['ora'] ?? ''),
            null,
            $errors,
            (int) ($dati['settimana'] ?? 0),
            $dati,
            self::prenotazioniIntervallo($sessioneRow),
            self::idsPilotiSanzionabili($gestoreId)
        );
    }

    /**carica Sessione solo se è del gestore cosi da evitare che un gestore veda sessioni di un altro*/
    private static function sessioneDelGestore(int $sessioneId, int $gestoreId): ?array
    {
        if ($sessioneId < 1) {
            return null;
        }

        return FPersistentManager::sessioneLoadByIdForGestore($sessioneId, $gestoreId);
    }

    /** vede se la sessione che si vuole annullare è esistente, del gestore e non già annullata (altrimenti redirect con messaggio) */
    private static function sessioneAnnullabile(int $sessioneId, int $gestoreId, int $settimana): array
    {
        if ($sessioneId < 1) {
            flash('error', 'Sessione non valida.');
            redirect('/calendario');
        }

        $sessioneRow = FPersistentManager::sessioneLoadByIdForGestore($sessioneId, $gestoreId);
        if ($sessioneRow === null) {
            flash('error', 'Sessione non trovata o non di tua proprietà.');
            redirect('/calendario');
        }
        if (($sessioneRow['stato'] ?? '') === 'annullata') {
            flash('warn', 'La sessione è già stata annullata.');
            redirect('/calendario?circuito_id=' . (int) $sessioneRow['circuito_id'] . '&settimana=' . $settimana);
        }

        return $sessioneRow;
    }

    /**traduce la sessionne nella terna circuito, inizio, fine per far vedere chi verrà rimborsato  */
    private static function pilotiConfermati(array $sessioneRow): array
    {
        return FPersistentManager::prenotazioneLoadConfermateByIntervalloCircuito(
            (int) $sessioneRow['circuito_id'],
            (string) $sessioneRow['inizio'],
            (string) $sessioneRow['fine']
        );
    }

    /** mapping degli errori quando si va a fare l'annullamento (per verificare che la richiesta sia legittima)*/
    private static function erroriRichiestaAnnullamento(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Richiesta non valida: conferma l\'annullamento dal form dedicato.'];
        }
        if (!csrf_check(post('csrf_token'))) {
            return ['Token CSRF non valido. Ricarica la pagina e riprova.'];
        }

        return [];
    }

    /** Annulla la sessione e se successo reindirizza al calendario, altrimenti restituisce gli errori.*/
    private static function eseguiAnnullamento(int $sessioneId, int $gestoreId, string $causa, array $sessioneRow, int $settimana): array
    {
        try {
            $count = FPersistentManager::sessioneAnnulla($sessioneId, $gestoreId, $causa);
            flash('ok', $count > 0
                ? 'Sessione annullata. Rimborso del 100% applicato a ' . $count . ($count === 1 ? ' pilota.' : ' piloti.')
                : 'Sessione annullata. Nessuna prenotazione attiva da rimborsare.');
            redirect('/calendario?circuito_id=' . (int) $sessioneRow['circuito_id'] . '&settimana=' . $settimana);
        } catch (InvalidArgumentException $e) {
            return [$e->getMessage()];
        } catch (Throwable) {
            return ["Errore durante l'annullamento. Riprova tra qualche istante."];
        }
    }

    /** Id dei piloti ancora sanzionabili dal gestore (mostrare il pulsante "Sanziona" solo per chinon è già sotto sanzione attiva) */
    private static function idsPilotiSanzionabili(int $gestoreId): array
    {
        return array_map(
            static fn(array $r): int => (int) $r['id'],
            FPersistentManager::sanzionePilotaPilotiSanzionabili($gestoreId)
        );
    }

    /** fa i check per gestore e circuito e carica calendario*/
    private static function renderCalendario(int $gestoreId, int $circuitoId, int $settimana, array $errors): void
    {
        $circuiti = FPersistentManager::circuitoLoadByGestore($gestoreId);
        if ($circuitoId === 0 && $circuiti !== []) {
            $circuitoId = (int) $circuiti[0]['id'];
        }
        if ($circuitoId > 0 && !FPersistentManager::circuitoIsDelGestore($circuitoId, $gestoreId)) {
            $errors[]   = 'Circuito non valido.';
            $circuitoId = $circuiti !== [] ? (int) $circuiti[0]['id'] : 0;
        }

        $cal = FPersistentManager::sessioneCalendarioSettimanale($circuitoId, $settimana);

        VGestoreCircuiti::calendario(
            $circuiti, $circuitoId, $cal['lunedi'], $cal['griglia'], $cal['ore'], $cal['settimana'],
            $errors, false,
            FPersistentManager::gestoreCircuitiIsAffiliazioneApprovata($gestoreId),
            FPersistentManager::gestoreCircuitiGetAffiliazione($gestoreId)
        );
    }

    /** Verifica autenticazione e ruolo gestore circuiti, restituisce l'id del gestore */
    private static function richiediGestoreCircuiti(): int
    {
        return CAuth::idUtenteConRuolo(EGestoreCircuiti::$ruolo);
    }

    /** mappatura dei dati da POST  */
    private static function datiDaPost(): array
    {
        return [
            'csrf_token'  => (string) post('csrf_token', ''),
            'circuito_id' => (int) post('circuito_id', '0'),
            'data'        => (string) post('data', ''),
            'ora'         => (string) post('ora', ''),
            'durata'      => (int) post('durata', '1'),
            'tariffa_accesso' => (string) post('tariffa_accesso', ''),
            'tariffa_valuta'  => (string) post('tariffa_valuta', 'EUR'),
            'posti_max'   => (int) post('posti_max', '1'),
            'posti_per_box' => (int) post('posti_per_box', '1'),
            'note'        => (string) post('note', ''),
            'sessione_id' => (int) post('sessione_id', '0'),
            'settimana'   => (int) post('settimana', '0'),
            'stato'       => (string) post('stato', 'privata'),
        ];
    }

    /** validazione della sessione: prima i campi del form, poi la capienza dei box e i conflitti di pianificazione */
    private static function validaSalvaSessione(array $dati, int $gestoreId): array
    {
        $parsed = self::parseDataOra((string) ($dati['data'] ?? ''), (string) ($dati['ora'] ?? ''));
        if ($parsed === null) {
            return ['Data o orario non validi.'];
        }

        $errors = array_merge(self::erroriCampiSessione($dati, $gestoreId), self::erroriTariffa($dati));
        if ($errors === []) {
            $errors = self::erroriCapienzaBox($dati);
        }
        if ($errors !== []) {
            return $errors;
        }

        return self::erroriPianificazione($dati, $gestoreId, $parsed);
    }

    /** test errori relativi a circuito, durata, posti e categoria della sessione */
    private static function erroriCampiSessione(array $dati, int $gestoreId): array
    {
        $errors = [];
        if (!FPersistentManager::circuitoIsDelGestore((int) ($dati['circuito_id'] ?? 0), $gestoreId)) {
            $errors[] = 'Circuito non valido o non di tua proprietà.';
        }

        $durata = (int) ($dati['durata'] ?? 1);
        if ($durata < self::DURATA_MIN || $durata > self::DURATA_MAX) {
            $errors[] = 'Durata non valida (da ' . self::DURATA_MIN . ' a ' . self::DURATA_MAX . ' ore).';
        }
        if ((int) ($dati['posti_max'] ?? 1) < 1 || (int) ($dati['posti_max'] ?? 1) > 99) {
            $errors[] = 'Indica un numero di posti tra 1 e 99.';
        }
        if ((int) ($dati['posti_per_box'] ?? 1) < 1 || (int) ($dati['posti_per_box'] ?? 1) > 99) {
            $errors[] = 'Indica un numero di posti per box tra 1 e 99.';
        }
        if (!in_array((string) ($dati['stato'] ?? 'privata'), ESessione::CATEGORIE, true)) {
            $errors[] = 'Categoria sessione non valida (amatoriale, professionistica o privata).';
        }

        return $errors;
    }

    /** test di errori riguardo la tariffa di accesso */
    private static function erroriTariffa(array $dati): array
    {
        $errors     = [];
        $tariffaRaw = str_replace(',', '.', trim((string) ($dati['tariffa_accesso'] ?? '')));
        if ($tariffaRaw === '') {
            $errors[] = 'La tariffa di accesso della sessione è obbligatoria.';
        } elseif (!is_numeric($tariffaRaw)) {
            $errors[] = 'La tariffa di accesso deve essere un importo numerico valido.';
        } elseif ((float) $tariffaRaw < 0) {
            $errors[] = 'La tariffa di accesso non può essere negativa.';
        }

        $valuta = strtoupper(trim((string) ($dati['tariffa_valuta'] ?? 'EUR')));
        if (!in_array($valuta, FPersistentManager::CAMBIO_VALUTA_SUPPORTATE, true)) {
            $errors[] = 'Valuta della tariffa non valida.';
        }

        return $errors;
    }

    /** test che i posti massimi rientrino nella capacità dei box del circuito */
    private static function erroriCapienzaBox(array $dati): array
    {
        $circuitoRow = FPersistentManager::circuitoLoadById((int) $dati['circuito_id']);
        $numeroBox   = (int) ($circuitoRow['numero_box'] ?? 0);
        if ($numeroBox < 1) {
            return ['Il circuito non ha box configurati: imposta il numero di box nel profilo circuito.'];
        }

        $postiMax    = (int) ($dati['posti_max'] ?? 1);
        $capacitaBox = $numeroBox * (int) ($dati['posti_per_box'] ?? 1);
        if ($postiMax > $capacitaBox) {
            return ['I posti massimi (' . $postiMax . ') superano la capacità dei box ('. $numeroBox . ' box × ' . (int) ($dati['posti_per_box'] ?? 1) . ' posti = ' . $capacitaBox . ').'];
        }

        return [];
    }

    /** test se ci sono conflitti di orario con sessioni e prenotazioni esistenti  */
    private static function erroriPianificazione(array $dati, int $gestoreId, array $parsed): array
    {
        $inizio = $parsed[0] . ' ' . $parsed[1] . ':00';
        try {
            $fine = (new DateTimeImmutable($inizio))->modify('+' . (int) $dati['durata'] . ' hours')->format('Y-m-d H:i:s');
        } catch (Exception) {
            return ['Intervallo orario non valido.'];
        }

        $errors     = [];
        $sessioneId = (int) ($dati['sessione_id'] ?? 0);
        if (FPersistentManager::sessioneEsisteConflitto((int) $dati['circuito_id'], $inizio, $fine, $sessioneId > 0 ? $sessioneId : null)) {
            $errors[] = 'Esiste già una sessione in questo intervallo.';
        }
        if ($sessioneId > 0) {
            return array_merge($errors, self::erroriModificaSessione($dati, $gestoreId, $sessioneId, $inizio, $fine));
        }
        if (FPersistentManager::sessioneEsisteConflittoPrenotazione((int) $dati['circuito_id'], $inizio, $fine)) {
            $errors[] = 'Intervallo in conflitto con una prenotazione attiva.';
        }

        return $errors;
    }

    /** test rispetto ai vincoli generici sulla modifica di una sessione esistente */
    private static function erroriModificaSessione(array $dati, int $gestoreId, int $sessioneId, string $inizio, string $fine): array
    {
        $originale = FPersistentManager::sessioneLoadByIdForGestore($sessioneId, $gestoreId);
        if ($originale === null) {
            return ['Sessione non trovata o non di tua proprietà.'];
        }

        $prenotate = FPersistentManager::sessioneCountPrenotazioniAttive(
            (int) $dati['circuito_id'],
            (string) $originale['inizio'],
            (string) $originale['fine']
        );
        $errors = self::erroriVincoliPrenotazioni($dati, $originale, $prenotate, $inizio, $fine);

        if (FPersistentManager::sessioneEsisteConflittoPrenotazioneEscludendoIntervallo(
            (int) $dati['circuito_id'], $inizio, $fine, (string) $originale['inizio'], (string) $originale['fine']
        )) {
            $errors[] = 'Il nuovo intervallo è in conflitto con prenotazioni di un\'altra sessione.';
        }

        return $errors;
    }

    /** test che i vincoli siano rispettati in base alle prenotazioni attive*/
    private static function erroriVincoliPrenotazioni(array $dati, array $originale, int $prenotate, string $inizio, string $fine): array
    {
        $errors = [];
        if ((int) $dati['posti_max'] < $prenotate) {
            $errors[] = 'Non puoi ridurre i posti a ' . (int) $dati['posti_max'] . ': ci sono già ' . $prenotate
                . ($prenotate === 1 ? ' prenotazione confermata.' : ' prenotazioni confermate.');
        }
        if ($prenotate > 0 && ($inizio !== (string) $originale['inizio'] || $fine !== (string) $originale['fine'])) {
            $errors[] = 'Non puoi modificare la durata di una sessione con prenotazioni attive: '
                . 'annulla la sessione (con rimborso ai piloti) per ripianificarla.';
        }
        if ($prenotate > 0 && (int) $dati['posti_per_box'] !== (int) ($originale['posti_per_box'] ?? 1)) {
            $errors[] = 'Non puoi modificare i posti per box con prenotazioni attive: '
                . 'annulla la sessione per ripianificarla.';
        }

        return $errors;
    }

    /** salvataggio della sessione nel database */
    private static function persistiSessione(array $dati): void
    {
        [$inizio, $fine] = self::intervalloSessione($dati);
        $note  = trim((string) ($dati['note'] ?? '')) ?: null;
        $stato = (string) ($dati['stato'] ?? 'privata'); // categoria: amatoriale/professionistica/privata

        // Tariffa nella valuta scelta dal gestore ma salvata SEMPRE in EUR
        $tariffa = FPersistentManager::cambioValutaInEuro(
            (float) str_replace(',', '.', trim((string) ($dati['tariffa_accesso'] ?? '0'))),
            (string) ($dati['tariffa_valuta'] ?? 'EUR')
        );

        $sessioneId = (int) ($dati['sessione_id'] ?? 0);
        if ($sessioneId > 0) {
            self::aggiornaSessioneEsistente($sessioneId, $dati, $inizio, $fine, $tariffa, $note, $stato);
            return;
        }

        FPersistentManager::sessioneStore(new ESessione(
            (int) $dati['circuito_id'], $inizio, $fine, $tariffa,
            (int) ($dati['posti_max'] ?? 1), (int) ($dati['posti_per_box'] ?? 1), $note, $stato
        ));
    }

    /**definizione dell'intervallo di tempo per la sessione */
    private static function intervalloSessione(array $dati): array
    {
        $parsed = self::parseDataOra((string) $dati['data'], (string) $dati['ora']);
        if ($parsed === null) {
            throw new RuntimeException('Data o orario non validi.');
        }

        $inizio = $parsed[0] . ' ' . $parsed[1] . ':00';

        return [$inizio, (new DateTimeImmutable($inizio))->modify('+' . (int) $dati['durata'] . ' hours')->format('Y-m-d H:i:s')];
    }

    /** conversione di data e ora nel formato Y-m-d, H:i */
    private static function parseDataOra(string $data, string $ora): ?array
    {
        $data = trim($data);
        $ora  = trim($ora);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}$/', $ora)) {
            return null;
        }
        if (!in_array($ora, FPersistentManager::SESSIONE_ORE_GIORNO, true)) {
            return null;
        }

        try {
            new DateTimeImmutable($data . ' ' . $ora . ':00');
        } catch (Exception) {
            return null;
        }

        return [$data, $ora];
    }

    /** verifica se lo slot è già occupato da una prenotazione attiva */
    private static function slotBloccato(int $circuitoId, string $data, string $ora): bool
    {
        $inizio = $data . ' ' . $ora . ':00';
        $fine   = (new DateTimeImmutable($inizio))->modify('+1 hour')->format('Y-m-d H:i:s');

        return FPersistentManager::sessioneEsisteConflittoPrenotazione($circuitoId, $inizio, $fine);
    }
}