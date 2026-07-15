<?php

/**
 * Controller del caso d'uso «Gestione flotta» (attore: gestore noleggio).
 *
 * Un metodo pubblico per ogni passo del flusso (vedi ROUTES, i tag 1a/2a/…
 * sono sui singoli metodi); nuovoVeicolo/creaVeicolo registrano i veicoli
 * in flotta.
 */
class CGestioneFlotta
{
    /** Azione «landing» invocata dall'URL «pulita» di sezione (senza azione). */
    public const DEFAULT_ACTION = 'gestisciFlotta';

    /** Tabella rotte «pulite» (URL → metodo); risolta da CFrontController. */
    public const ROUTES = [
        'GET nuovo' => 'nuovoVeicolo',
        'POST nuovo' => 'creaVeicolo',
        'GET circuito/{id}' => 'elencaVeicoli',
        'GET veicolo/{id}' => 'visualizzaVeicolo',
        'GET veicolo/{id}/modifica' => 'modificaVeicolo',
        'POST veicolo/{id}/modifica' => 'aggiornaVeicolo',
        'POST veicolo/{id}/elimina' => 'eliminaVeicolo',
    ];

    /** GET — elenco circuiti con veicoli dell'azienda (1a/1b). */
    public function gestisciFlotta(): void
    {
        $aziendaId = self::aziendaCorrente();

        VGestoreNoleggio::elencoCircuiti(
            FPersistentManager::veicoloNoleggioLoadCircuitiByAzienda($aziendaId),
            []
        );
    }

    /** GET — form vuoto per l'aggiunta di un veicolo alla flotta. */
    public function nuovoVeicolo(): void
    {
        self::aziendaCorrente();

        VGestoreNoleggio::formAggiungiVeicolo(FPersistentManager::circuitoLoadElenco());
    }

    /** POST — valida, crea il veicolo e mostra conferma o errori ($dati vuoto: lettura da POST). */
    public function creaVeicolo(array $dati = []): void
    {
        $aziendaId = self::aziendaCorrente();
        $dati      = $dati !== [] ? $dati : self::datiDaPost();

        $errors = self::erroriRichiesta($dati) ?: self::validaCampiVeicolo($dati);
        if ($errors !== []) {
            VGestoreNoleggio::formAggiungiVeicolo(FPersistentManager::circuitoLoadElenco(), $dati, $errors);
            return;
        }

        try {
            $creato = FPersistentManager::veicoloNoleggioCreaDaForm($aziendaId, $dati);
            VGestoreNoleggio::confermaAggiuntaVeicolo(
                FPersistentManager::circuitoLoadById((int) $creato['circuito_id']),
                $creato
            );
        } catch (Throwable $ex) {
            VGestoreNoleggio::formAggiungiVeicolo(
                FPersistentManager::circuitoLoadElenco(),
                $dati,
                ['Errore durante il salvataggio: ' . $ex->getMessage()]
            );
        }
    }

    /** GET — veicoli della flotta sul circuito selezionato (2a/2b). */
    public function elencaVeicoli(int|string $circuito = 0): void
    {
        $aziendaId  = self::aziendaCorrente();
        $circuitoId = self::parseId($circuito, 'circuito_id');
        $catFilter  = (string) ($_GET['cat'] ?? '');

        [$circuitoRow, $errors] = self::caricaCircuito($circuitoId);
        $veicoli = $errors === []
            ? FPersistentManager::veicoloNoleggioLoadByCircuitoAndAzienda(
                $circuitoId,
                $aziendaId,
                $catFilter !== '' ? $catFilter : null
            )
            : [];

        VGestoreNoleggio::elencoVeicoli($circuitoRow, $veicoli, $catFilter, $errors);
    }

    /** GET — scheda dettaglio del veicolo selezionato (3a/3b). */
    public function visualizzaVeicolo(int|string $veicolo = 0): void
    {
        $aziendaId = self::aziendaCorrente();

        [$veicoloRow, $circuito, $errors] = self::caricaVeicoloConCircuito(
            self::parseId($veicolo, 'veicolo_id'),
            $aziendaId
        );

        VGestoreNoleggio::dettaglioVeicolo($circuito, $veicoloRow, $errors);
    }

    /** GET — form di modifica precompilato con i dati attuali del veicolo (4a/4b). */
    public function modificaVeicolo(int|string $veicolo = 0): void
    {
        $aziendaId = self::aziendaCorrente();

        [$veicoloRow, $circuito, $errors] = self::caricaVeicoloConCircuito(
            self::parseId($veicolo, 'veicolo_id'),
            $aziendaId
        );

        VGestoreNoleggio::formModificaVeicolo(
            $circuito,
            $veicoloRow,
            FPersistentManager::circuitoLoadElenco(),
            [],
            $errors
        );
    }

    /**
     * POST — valida, salva e mostra il riepilogo aggiornato (5a/5b). L'id del
     * veicolo arriva dall'URL, i dati del form dal body POST.
     */
    public function aggiornaVeicolo(int|string $veicolo = 0): void
    {
        $aziendaId = self::aziendaCorrente();
        $dati      = self::datiDaPost();
        if ((int) $veicolo > 0) {
            $dati['veicolo_id'] = (int) $veicolo;
        }

        $errors = self::erroriRichiesta($dati) ?: self::validaSalvaDati($dati, $aziendaId);
        if ($errors !== []) {
            self::mostraFormModificaConErrori($aziendaId, $dati, $errors);
            return;
        }

        $aggiornato = FPersistentManager::veicoloNoleggioAggiornaDaForm((int) $dati['veicolo_id'], $aziendaId, $dati);
        VGestoreNoleggio::riepilogo(
            FPersistentManager::circuitoLoadById((int) $aggiornato['circuito_id']),
            $aggiornato
        );
    }

    /**
     * POST — elimina un veicolo dalla flotta. Consentito solo senza
     * prenotazioni associate (integrità referenziale + storico fatture);
     * altrimenti si suggerisce di marcarlo «non disponibile».
     */
    public function eliminaVeicolo(int|string $veicolo = 0): void
    {
        $aziendaId  = self::aziendaCorrente();
        $veicoloId  = self::parseId($veicolo, 'veicolo_id');
        $veicoloRow = $veicoloId > 0
            ? FPersistentManager::veicoloNoleggioLoadByIdAndAzienda($veicoloId, $aziendaId)
            : null;

        $errors = self::erroriEliminazione($veicoloRow);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect($veicoloRow !== null ? '/flotta/veicolo/' . $veicoloId : '/flotta');
        }

        $circuitoId = (int) $veicoloRow['circuito_id'];
        try {
            FPersistentManager::veicoloNoleggioDeleteByIdAndAzienda($veicoloId, $aziendaId);
            flash('ok', 'Veicolo eliminato dalla flotta.');
        } catch (Throwable) {
            flash('error', "Errore durante l'eliminazione del veicolo. Riprova tra qualche istante.");
            redirect('/flotta/veicolo/' . $veicoloId);
        }

        redirect($circuitoId > 0 ? '/flotta/circuito/' . $circuitoId : '/flotta');
    }

    /** Guard di ruolo azienda di noleggio + id dell'azienda loggata. */
    private static function aziendaCorrente(): int
    {
        return CAuth::idUtenteConRuolo(EGestoreNoleggio::$ruolo);
    }

    /** Id dal segmento URL, con fallback sul parametro GET omonimo. */
    private static function parseId(int|string $valore, string $parametroGet): int
    {
        if ($valore !== 0 && $valore !== '') {
            return (int) $valore;
        }

        return (int) ($_GET[$parametroGet] ?? 0);
    }

    /** Verifica POST + token CSRF del form veicolo. */
    private static function erroriRichiesta(array $dati): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Invio del form non valido.'];
        }
        if (!csrf_check(isset($dati['csrf_token']) ? (string) $dati['csrf_token'] : null)) {
            return ['Token CSRF non valido.'];
        }

        return [];
    }

    /** @return array{0: array<string, mixed>|null, 1: list<string>} */
    private static function caricaCircuito(int $circuitoId): array
    {
        if ($circuitoId <= 0) {
            return [null, ['Seleziona un circuito valido.']];
        }

        $row = FPersistentManager::circuitoLoadById($circuitoId);

        return $row ? [$row, []] : [null, ['Il circuito richiesto non esiste.']];
    }

    /**
     * Carica veicolo (con controllo di ownership) e circuito associato.
     *
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: list<string>}
     */
    private static function caricaVeicoloConCircuito(int $veicoloId, int $aziendaId): array
    {
        if ($veicoloId <= 0) {
            return [null, null, ['Identificativo veicolo non valido.']];
        }

        $veicolo = FPersistentManager::veicoloNoleggioLoadByIdAndAzienda($veicoloId, $aziendaId);
        if (!$veicolo) {
            return [null, null, ['Il veicolo non esiste o è stato rimosso dalla flotta.']];
        }

        $circuito = FPersistentManager::circuitoLoadById((int) $veicolo['circuito_id']);

        return [$veicolo, $circuito, $circuito ? [] : ['Il circuito associato al veicolo non è più disponibile.']];
    }

    /** Ripropone il form di modifica con gli errori di validazione. */
    private static function mostraFormModificaConErrori(int $aziendaId, array $dati, array $errors): void
    {
        $veicoloId = (int) ($dati['veicolo_id'] ?? 0);
        [$veicoloRow, $circuito, $erroriCarico] = $veicoloId > 0
            ? self::caricaVeicoloConCircuito($veicoloId, $aziendaId)
            : [null, null, []];

        VGestoreNoleggio::formModificaVeicolo(
            $circuito,
            $veicoloRow,
            FPersistentManager::circuitoLoadElenco(),
            $dati,
            array_values(array_unique(array_merge($errors, $erroriCarico)))
        );
    }

    private static function erroriEliminazione(?array $veicoloRow): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Richiesta non valida: usa il pulsante di eliminazione.'];
        }
        if (!csrf_check(post('csrf_token'))) {
            return ['Token CSRF non valido.'];
        }
        if ($veicoloRow === null) {
            return ['Il veicolo non esiste o è stato rimosso dalla flotta.'];
        }
        if ((int) ($veicoloRow['pren_count'] ?? 0) > 0) {
            return ['Impossibile eliminare il veicolo: ha prenotazioni associate. '
                . 'Impostalo come «non disponibile» per ritirarlo dalla flotta.'];
        }

        return [];
    }

    private static function datiDaPost(): array
    {
        return [
            'csrf_token'   => post('csrf_token'),
            'veicolo_id'   => post('veicolo_id', '0'),
            'circuito_id'  => post('circuito_id', '0'),
            'targa'        => post('targa'),
            'categoria'    => post('categoria', 'auto'),
            'marca'        => post('marca'),
            'modello'      => post('modello'),
            'anno'         => post('anno'),
            'potenza_cv'   => post('potenza_cv'),
            'capienza'     => post('capienza', '1'),
            'prezzo_giorno'=> post('prezzo_giorno'),
            'prezzo_valuta'=> post('prezzo_valuta', 'EUR'),
            'disponibile'  => post('disponibile', '1'),
        ];
    }

    /**
     * Validazione dell'aggiornamento: esistenza del veicolo, poi controlli sui
     * campi (escludendo la targa del veicolo stesso).
     */
    private static function validaSalvaDati(array $dati, int $aziendaId): array
    {
        $veicoloId = (int) ($dati['veicolo_id'] ?? 0);
        if ($veicoloId <= 0) {
            return ['Veicolo da aggiornare non specificato.'];
        }
        if (!FPersistentManager::veicoloNoleggioLoadByIdAndAzienda($veicoloId, $aziendaId)) {
            return ['Il veicolo non esiste o è stato rimosso dalla flotta.'];
        }

        return self::validaCampiVeicolo($dati, $veicoloId);
    }

    /**
     * Controlli sui campi del veicolo, condivisi tra creazione e modifica;
     * $escludiTargaId evita la collisione con la targa del veicolo stesso.
     */
    private static function validaCampiVeicolo(array $dati, int $escludiTargaId = 0): array
    {
        return array_merge(
            self::erroriAssociazioneVeicolo($dati, $escludiTargaId),
            self::erroriCaratteristicheVeicolo($dati)
        );
    }

    /** Circuito di destinazione, targa e categoria. */
    private static function erroriAssociazioneVeicolo(array $dati, int $escludiTargaId): array
    {
        $errors     = [];
        $circuitoId = (int) ($dati['circuito_id'] ?? 0);
        if ($circuitoId <= 0) {
            $errors[] = 'Seleziona il circuito a cui associare il veicolo.';
        } elseif (FPersistentManager::circuitoLoadById($circuitoId) === null) {
            $errors[] = 'Il circuito selezionato non esiste.';
        }

        $targa = targa_normalizza((string) ($dati['targa'] ?? ''));
        if ($targa === '') {
            $errors[] = 'La targa è obbligatoria.';
        } elseif (!targa_valida($targa)) {
            $errors[] = 'Targa non valida: usa il formato italiano (auto AA000AA o moto AA00000).';
        } elseif (FPersistentManager::veicoloNoleggioTargaInUso($targa, $escludiTargaId)) {
            $errors[] = 'La targa indicata è già assegnata a un altro veicolo.';
        }

        if (!in_array((string) ($dati['categoria'] ?? ''), ['auto', 'moto'], true)) {
            $errors[] = 'Categoria veicolo non valida.';
        }

        return $errors;
    }

    /** Capienza, anno, potenza + delega a prezzo/disponibilità. */
    private static function erroriCaratteristicheVeicolo(array $dati): array
    {
        $errors = [];
        if ((int) ($dati['capienza'] ?? 0) < 1) {
            $errors[] = 'La capienza deve essere almeno 1 posto.';
        }

        $annoRaw = trim((string) ($dati['anno'] ?? ''));
        if ($annoRaw !== '' && ((int) $annoRaw < 1900 || (int) $annoRaw > (int) date('Y') + 1)) {
            $errors[] = 'Anno di immatricolazione non valido.';
        }

        $potenzaRaw = trim((string) ($dati['potenza_cv'] ?? ''));
        if ($potenzaRaw !== '' && (int) $potenzaRaw < 0) {
            $errors[] = 'La potenza non può essere negativa.';
        }

        return array_merge($errors, self::erroriPrezzoEDisponibilita($dati));
    }

    /** Prezzo di listino (in valuta) e stato di disponibilità. */
    private static function erroriPrezzoEDisponibilita(array $dati): array
    {
        $errors = [];
        if ((float) str_replace(',', '.', (string) ($dati['prezzo_giorno'] ?? '0')) <= 0) {
            $errors[] = 'Inserisci un prezzo listino valido maggiore di zero.';
        }

        // Il prezzo può arrivare in una valuta diversa: viene comunque
        // convertito e salvato in EUR.
        $valuta = strtoupper(trim((string) ($dati['prezzo_valuta'] ?? 'EUR')));
        if (!in_array($valuta, FPersistentManager::CAMBIO_VALUTA_SUPPORTATE, true)) {
            $errors[] = 'Valuta del prezzo non valida.';
        }

        if (!in_array((string) ($dati['disponibile'] ?? ''), ['0', '1'], true)) {
            $errors[] = 'Stato disponibilità non valido.';
        }

        return $errors;
    }
}
