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

}