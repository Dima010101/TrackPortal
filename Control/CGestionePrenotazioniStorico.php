<?php

/**
 * CGestionePrenotazioniStorico ( caso d'uso
 * Gestione prenotazioni e storico)
 * attore: utente registrato/pilota).
 *
 * Operazioni di sistema (1 metodo per interazione utente-sistema):
 *  - elencaPrenotazioni()               → 1a/1b: elenco filtrabile attive/storico
 *  - visualizzaPrenotazione()           → 2a/2b: dettaglio con fatture scaricabili
 *  - modificaPrenotazione()             → 3a: form con i soli campi modificabili
 *  - aggiornaPrenotazione()             → 3b: salvataggio ed esito della modifica
 *  - annullaPrenotazione()              → 4a: motivazione + rimborso previsto
 *  - confermaAnnullamentoPrenotazione() → 4b: cancellazione definitiva
 *  - scaricaFatturaPdf() / scaricaDocumentoFiscale() → 2b: download delle fatture
 *    dal dettaglio (e dallo storico visibile a gestore/azienda di noleggio)
 *  - elencaStorico()                    → storico prenotazioni lato gestori
 */
class CGestionePrenotazioniStorico
{
    /** Azione «landing» invocata dall'URL «pulita» di sezione (senza azione). */
    public const DEFAULT_ACTION = 'elencaPrenotazioni';

    /** Tabella rotte «pulite» (URL → metodo); risolta da CFrontController. */
    public const ROUTES = [
        'GET storico' => 'elencaStorico',
        'POST storico' => 'elencaStorico',
        'GET {id}' => 'visualizzaPrenotazione',
        'GET {id}/modifica' => 'modificaPrenotazione',
        'POST {id}/modifica' => 'aggiornaPrenotazione',
        'GET {id}/cancella' => 'annullaPrenotazione',
        'POST {id}/cancella' => 'confermaAnnullamentoPrenotazione',
        'GET {id}/fattura' => 'scaricaFatturaPdf',
        'GET documento/{id}' => 'scaricaDocumentoFiscale',
    ];

    /** GET — elenco prenotazioni dell'utente loggato (1a/1b). */
    public function elencaPrenotazioni(): void
    {
        self::richiediPilota();
        $user   = CAuth::utenteCorrente();
        $filtro = (string) ($_GET['stato'] ?? 'attive');
        $q      = trim((string) ($_GET['q'] ?? ''));

        if (!in_array($filtro, ['attive', 'storico'], true)) {
            flash('error', 'Filtro elenco non valido.');
            redirect('/prenotazioni?stato=attive');
        }

        try {
            $rows = FPersistentManager::prenotazioneLoadByPilotaFiltro((int) $user['id'], $filtro, $q);
        } catch (Throwable) {
            flash('error', 'Impossibile caricare le prenotazioni. Riprova tra qualche istante.');
            redirect('/dashboard');
        }

        VPrenotazione::lista($rows, $filtro, $q);
    }

    /** GET — storico prenotazioni per i gestori (circuiti / noleggio). */
    public function elencaStorico(): void
    {
        CAuth::richiediRuolo([EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo]);
        $user = CAuth::utenteCorrente();
        $uid  = (int) $user['id'];
        $q    = trim((string) ($_GET['q'] ?? ''));

        // Gli id dei piloti ancora sanzionabili distinguono nel template il
        // pulsante "Sanziona" da "Già sanzionato".
        if ($user['ruolo'] === EGestoreCircuiti::$ruolo) {
            VGestoreCircuiti::storico(
                FPersistentManager::prenotazioneLoadByGestoreForFatturazione($uid, $q),
                $q,
                self::idsPiloti(FPersistentManager::sanzionePilotaPilotiSanzionabili($uid))
            );
            return;
        }

        VGestoreNoleggio::storico(
            FPersistentManager::prenotazioneLoadByAziendaForFatturazione($uid, $q),
            $q,
            self::idsPiloti(FPersistentManager::sanzionePilotaNoleggioPilotiSanzionabili($uid))
        );
    }

    /** GET — dettaglio di una prenotazione, con fatture e rimborso previsto (2a/2b). */
    public function visualizzaPrenotazione(int|string $id_prenotazione = 0): void
    {
        self::richiediPilota();
        $user = CAuth::utenteCorrente();
        $pren = self::prenotazioneOppureRedirect(self::parseId($id_prenotazione), (int) $user['id']);
        $id   = (int) $pren['id'];

        // Fatture intestate al pilota per questa prenotazione (circuito,
        // noleggio, assicurazione), scaricabili dal dettaglio.
        $fatture = FPersistentManager::documentoFiscaleFatturePilotaPerPrenotazioni([$id], (int) $user['id'])[$id] ?? [];

        [$rimborsoPerc, $rimborsoStimato] = self::rimborsoPrevisto($pren);
        VPrenotazione::dettaglio($pren, false, $fatture, $rimborsoPerc, $rimborsoStimato);
    }

    /** GET — form di modifica precompilato (3a). */
    public function modificaPrenotazione(int|string $id_prenotazione = 0): void
    {
        self::richiediPilota();
        $user = CAuth::utenteCorrente();
        $pren = self::prenotazioneOppureRedirect(self::parseId($id_prenotazione), (int) $user['id']);

        $errModifica = self::erroreModificaConsentita($pren);
        if ($errModifica !== null) {
            flash('error', $errModifica);
            redirect('/prenotazioni/' . (int) $pren['id']);
        }

        VPrenotazione::formModifica(
            $pren,
            self::datiFormDaPrenotazione($pren),
            [],
            FPersistentManager::configurazionePiattaformaPrezzoAssicurazione()
        );
    }

    /**
     * POST — salva le modifiche inviate dal form (3b).
     *
     * @param array<string, mixed> $nuovi_dati
     */
    public function aggiornaPrenotazione(int|string $id_prenotazione = 0, array $nuovi_dati = []): void
    {
        self::richiediPilota();
        $id         = self::parseId($id_prenotazione);
        $user       = CAuth::utenteCorrente();
        $nuovi_dati = $nuovi_dati !== [] ? $nuovi_dati : self::datiDaPost();

        $pren   = $id > 0 ? self::caricaPrenotazione($id, (int) $user['id']) : null;
        $errors = self::erroriAggiornamento($id, $pren, $nuovi_dati);
        if ($errors !== []) {
            self::mostraEsitoModifica($pren, $nuovi_dati, $errors);
            return;
        }

        try {
            $aggiornato = FPersistentManager::prenotazioneAggiornaPrenotazione($id, (int) $user['id'], $nuovi_dati);
            VPrenotazione::confermaModifica(true, $aggiornato, []);
        } catch (InvalidArgumentException $e) {
            self::mostraEsitoModifica($pren, $nuovi_dati, [$e->getMessage()]);
        } catch (Throwable) {
            self::mostraEsitoModifica($pren, $nuovi_dati, ['Errore durante il salvataggio. Riprova tra qualche istante.']);
        }
    }

    /** GET — schermata di cancellazione con rimborso e motivazione (4a). */
    public function annullaPrenotazione(int|string $id_prenotazione = 0): void
    {
        self::richiediPilota();
        $user = CAuth::utenteCorrente();
        $pren = self::prenotazioneOppureRedirect(self::parseId($id_prenotazione), (int) $user['id']);

        $errCancellazione = self::erroreCancellazioneConsentita($pren);
        if ($errCancellazione !== null) {
            flash('error', $errCancellazione);
            redirect('/prenotazioni/' . (int) $pren['id']);
        }

        [$rimborsoPerc, $rimborso] = self::rimborsoPrevisto($pren);
        VPrenotazione::formCancellazione($pren, $rimborso, [], '', $rimborsoPerc);
    }

    /**
     * POST — elabora la cancellazione definitiva (4b).
     *
     * @param string $causa Motivazione; se vuota viene letta dal POST.
     */
    public function confermaAnnullamentoPrenotazione(int|string $id_prenotazione = 0, string $causa = ''): void
    {
        self::richiediPilota();
        $id    = self::parseId($id_prenotazione);
        $user  = CAuth::utenteCorrente();
        $causa = $causa !== '' ? $causa : (string) post('causa', '');

        $pren   = $id > 0 ? self::caricaPrenotazione($id, (int) $user['id']) : null;
        $errors = self::erroriAnnullamento($id, $pren);
        [$rimborsoPerc, $rimborso] = $pren !== null ? self::rimborsoPrevisto($pren) : [0, 0.0];

        if ($errors !== []) {
            if ($pren === null) {
                flash('error', implode(' ', $errors));
                redirect('/prenotazioni');
            }
            VPrenotazione::formCancellazione($pren, $rimborso, $errors, $causa, $rimborsoPerc);
            return;
        }

        self::eseguiCancellazione($id, (int) $user['id'], $causa, $pren, $rimborso, $rimborsoPerc);
    }

    // ---- Download delle fatture dal dettaglio/storico (2b del caso d'uso) ----

    /** GET /prenotazioni/{id}/fattura — PDF della ricevuta della prenotazione. */
    public function scaricaFatturaPdf(int|string $id = 0): void
    {
        CAuth::richiediRuolo([EPilota::$ruolo, EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo]);
        $user = CAuth::utenteCorrente();

        $data = self::caricaPerRuolo((string) $user['ruolo'], (int) $user['id'], (int) $id);
        if (!$data) {
            flash('error', 'Fattura non trovata o non autorizzata.');
            redirect((string) $user['ruolo'] === EPilota::$ruolo ? '/prenotazioni' : '/prenotazioni/storico');
        }

        FPersistentManager::fatturaPdfInvia($data['pren'], $data['vista']);
    }

    /** GET /prenotazioni/documento/{id} — PDF di un documento fiscale persistito. */
    public function scaricaDocumentoFiscale(int|string $id = 0): void
    {
        CAuth::richiediRuolo([EPilota::$ruolo, EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo]);
        $user = CAuth::utenteCorrente();

        $doc = FPersistentManager::documentoFiscaleLoadById((int) $id);
        if ($doc === null
            || !FPersistentManager::documentoFiscaleUtenteAutorizzato($doc, (string) $user['ruolo'], (int) $user['id'])) {
            flash('error', 'Documento non trovato o non autorizzato.');
            redirect((string) $user['ruolo'] === EPilota::$ruolo ? '/prenotazioni?stato=storico' : '/prenotazioni/storico');
        }

        FPersistentManager::fatturaPdfInviaDocumento($doc, FPersistentManager::documentoFiscaleLoadRighe((int) $doc['id']));
    }

    private static function richiediPilota(): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
    }

    private static function parseId(int|string $id): int
    {
        if ($id !== 0 && $id !== '') {
            return (int) $id;
        }

        return (int) ($_GET['id'] ?? post('id', '0'));
    }

    /** @param list<array<string, mixed>> $righe @return list<int> */
    private static function idsPiloti(array $righe): array
    {
        return array_map(static fn(array $r): int => (int) $r['id'], $righe);
    }

    /** @return array<string, mixed>|null */
    private static function caricaPrenotazione(int $id, int $pilotaId): ?array
    {
        try {
            return FPersistentManager::prenotazioneLoadDettaglio($id, $pilotaId);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Prenotazione del pilota loggato, o redirect all'elenco con messaggio.
     *
     * @return array<string, mixed>
     */
    private static function prenotazioneOppureRedirect(int $id, int $pilotaId): array
    {
        if ($id <= 0) {
            flash('error', 'Identificativo prenotazione non valido.');
            redirect('/prenotazioni');
        }

        $pren = self::caricaPrenotazione($id, $pilotaId);
        if (!$pren) {
            flash('error', 'Prenotazione non trovata o non autorizzata.');
            redirect('/prenotazioni');
        }

        return $pren;
    }

    /**
     * Percentuale e importo del rimborso in caso di annullamento (dipendono da
     * assicurazione e anticipo rispetto alla sessione); decisione nel Model.
     *
     * @param array<string, mixed> $pren
     * @return array{0: float|int, 1: float}
     */
    private static function rimborsoPrevisto(array $pren): array
    {
        $assicurato = !empty($pren['assicurazione']);
        $inizio     = (string) ($pren['inizio_sessione'] ?? '');

        return [
            EPrenotazione::percentualeRimborso($assicurato, $inizio),
            EPrenotazione::calcolaRimborsoCancellazione((float) ($pren['prezzo_importo'] ?? 0), $assicurato, $inizio),
        ];
    }

    /** @param array<string, mixed> $pren */
    private static function erroreModificaConsentita(array $pren): ?string
    {
        if (($pren['stato'] ?? '') !== 'confermata') {
            return 'Solo le prenotazioni confermate possono essere modificate.';
        }
        if (strtotime((string) ($pren['fine_sessione'] ?? '')) < time()) {
            return 'La prenotazione è già conclusa e non può essere modificata.';
        }

        return null;
    }

    /** @param array<string, mixed> $pren */
    private static function erroreCancellazioneConsentita(array $pren): ?string
    {
        if (($pren['stato'] ?? '') !== 'confermata') {
            return 'Solo le prenotazioni confermate possono essere annullate.';
        }
        if (strtotime((string) ($pren['fine_sessione'] ?? '')) < time()) {
            return 'La prenotazione è già conclusa: la trovi nello storico e non può essere annullata.';
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $pren
     * @param array<string, mixed> $dati
     * @return list<string>
     */
    private static function erroriAggiornamento(int $id, ?array $pren, array $dati): array
    {
        $errors = [];
        if ($id <= 0) {
            $errors[] = 'Identificativo prenotazione non valido.';
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'Richiesta non valida: invia il form per confermare la modifica.';
        } elseif (!csrf_check(isset($dati['csrf_token']) ? (string) $dati['csrf_token'] : null)) {
            $errors[] = 'Token CSRF non valido. Ricarica la pagina e riprova.';
        }
        if ($errors === [] && !$pren) {
            $errors[] = 'Prenotazione non trovata o non autorizzata.';
        }
        if ($errors === [] && ($errModifica = self::erroreModificaConsentita($pren)) !== null) {
            $errors[] = $errModifica;
        }

        return $errors;
    }

    /** @param array<string, mixed>|null $pren @return list<string> */
    private static function erroriAnnullamento(int $id, ?array $pren): array
    {
        $errors = [];
        if ($id <= 0) {
            $errors[] = 'Identificativo prenotazione non valido.';
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'Richiesta non valida: conferma la cancellazione dal form dedicato.';
        } elseif (!csrf_check(post('csrf_token'))) {
            $errors[] = 'Token CSRF non valido. Ricarica la pagina e riprova.';
        }
        if ($errors === [] && !$pren) {
            $errors[] = 'Prenotazione non trovata o non autorizzata.';
        }
        if ($errors === [] && ($errCanc = self::erroreCancellazioneConsentita($pren)) !== null) {
            $errors[] = $errCanc;
        }

        return $errors;
    }

    /**
     * Esito della modifica: form ripopolato (se la prenotazione esiste) o
     * schermata di esito negativo.
     *
     * @param array<string, mixed>|null $pren
     * @param array<string, mixed> $dati
     * @param list<string> $errors
     */
    private static function mostraEsitoModifica(?array $pren, array $dati, array $errors): void
    {
        if ($pren) {
            VPrenotazione::formModifica(
                $pren,
                self::mergeForm($pren, $dati),
                $errors,
                FPersistentManager::configurazionePiattaformaPrezzoAssicurazione()
            );
            return;
        }

        VPrenotazione::confermaModifica(false, [], $errors);
    }

    /** @param array<string, mixed> $pren */
    private static function eseguiCancellazione(int $id, int $pilotaId, string $causa, array $pren, float $rimborso, float|int $rimborsoPerc): void
    {
        try {
            FPersistentManager::prenotazioneCancella($id, $pilotaId, $causa, $rimborso);
            VPrenotazione::confermaCancellazione(true, $pren, $rimborso, [], $rimborsoPerc);
        } catch (InvalidArgumentException $e) {
            VPrenotazione::formCancellazione($pren, $rimborso, [$e->getMessage()], $causa, $rimborsoPerc);
        } catch (Throwable) {
            VPrenotazione::formCancellazione(
                $pren,
                $rimborso,
                ['Errore durante la cancellazione. Riprova tra qualche istante.'],
                $causa,
                $rimborsoPerc
            );
        }
    }

    /** @param array<string, mixed> $pren
     *  @return array<string, mixed>
     */
    private static function datiFormDaPrenotazione(array $pren): array
    {
        return [
            'prenotazione_id' => (int) ($pren['id'] ?? 0),
            'targa_veicolo'   => (string) ($pren['targa_veicolo'] ?? ''),
            'assicurazione'   => !empty($pren['assicurazione']) ? '1' : '0',
        ];
    }

    /** @param array<string, mixed> $pren
     *  @param array<string, mixed> $dati
     *  @return array<string, mixed>
     */
    private static function mergeForm(array $pren, array $dati): array
    {
        return array_merge(self::datiFormDaPrenotazione($pren), $dati);
    }

    /** @return array<string, mixed> */
    private static function datiDaPost(): array
    {
        return [
            'csrf_token'    => post('csrf_token'),
            'targa_veicolo' => post('targa_veicolo'),
            'assicurazione' => post('assicurazione', '0'),
        ];
    }

    /**
     * Prenotazione e vista fattura in base al ruolo (pilota, gestore circuito,
     * azienda di noleggio), con controllo di autorizzazione implicito nella query.
     *
     * @return array{pren: array<string, mixed>, vista: string}|null
     */
    private static function caricaPerRuolo(string $ruolo, int $uid, int $id): ?array
    {
        [$pren, $vista] = match ($ruolo) {
            EPilota::$ruolo          => [FPersistentManager::prenotazioneLoadDettaglio($id, $uid), 'pilota'],
            EGestoreCircuiti::$ruolo => [FPersistentManager::prenotazioneLoadDettaglioForGestore($id, $uid), 'gestore'],
            EGestoreNoleggio::$ruolo => [FPersistentManager::prenotazioneLoadDettaglioForAzienda($id, $uid), 'noleggio'],
            default                  => [null, ''],
        };

        return $pren ? ['pren' => $pren, 'vista' => $vista] : null;
    }
}
