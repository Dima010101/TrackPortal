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
            (int) ($_GET['circuito_id'] ?? 0),
            (int) ($_GET['settimana'] ?? 0),
            []
        );
    }

    /** scheda sessione per lo slot selezionato (2a/2b). GET /calendario/slot/{data}/{ora} */
    public function selezionaSlot(string $data = '', string $ora = ''): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) ($_GET['circuito_id'] ?? 0);

        [$sessione, $errors, $data, $ora] = self::caricaSlot($circuitoId, $gestoreId, $data, $ora);

        VGestoreCircuiti::schedaSessione(
            $circuitoId > 0 ? FPersistentManager::circuitoLoadById($circuitoId) : null,
            $data,
            $ora,
            $sessione,
            $errors,
            (int) ($_GET['settimana'] ?? 0),
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
        $sessioneId = (int) ($sessione ?: ($_GET['sessione_id'] ?? 0));
        $settimana  = (int) ($_GET['settimana'] ?? 0);

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

    
}