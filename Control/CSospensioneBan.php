<?php

/**
 * Controller per la gestione di ban e sospensioni dei piloti.
 */
class CSospensioneBan
{
    /** Azione di default (quando non viene specificata alcuna rotta). */
    public const DEFAULT_ACTION = 'gestisciSanzioni';

    /** Rotte disponibili per questo controller.
     * La chiave è il metodo HTTP seguito dalla rotta, il valore è il nome del metodo da invocare.
     */
    public const ROUTES = [
        'POST applica' => 'applicaSanzione',
        'POST {id}/revoca' => 'revocaSanzione',
        'POST {id}/modifica' => 'aggiornaPeriodoSanzione',
    ];

    /**
     * GET — mostra la pagina di gestione dei ban e delle sospensioni .
     * Se viene passato un parametro pilota_id, il pilota viene preselezionato.
     */
    public function gestisciSanzioni(): void
    {
        [$emittenteId, $ruolo] = self::emittente();

        // Preselezione del pilota quando si arriva dal pulsante "Sanziona"
        // (/...?pilota_id=N). Il tipo resta libero (ban o sospensione).
        $pilotaId = (int) get('pilota_id', '0');
        self::mostra($emittenteId, $ruolo, [], $pilotaId > 0 ? ['pilota_id' => $pilotaId] : []);
    }

    /**
     * POST — applica una sanzione a un pilota.
     */
    public function applicaSanzione(array $dati = []): void
    {
        [$emittenteId, $ruolo] = self::emittente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(self::rotta($ruolo));
        }
        $dati = $dati !== [] ? $dati : self::datiDaPost();

        $errors = !csrf_check((string) ($dati['csrf_token'] ?? ''))
            ? ['Token di sicurezza non valido. Ricarica la pagina e riprova.']
            : self::valida($dati, $emittenteId, $ruolo);
        if ($errors !== []) {
            self::mostra($emittenteId, $ruolo, $errors, $dati);
            return;
        }

        self::eseguiApplicazione($emittenteId, $ruolo, $dati);
    }

    /** POST — revoca una sanzione attiva dell'emittente. */
    public function revocaSanzione(int|string $id = 0): void
    {
        [$emittenteId, $ruolo] = self::emittente();
        self::richiediPostConCsrf($ruolo);

        $sanzioneId = (int) ($id ?: post('sanzione_id', '0'));
        if (FPersistentManager::sanzioneRevoca(self::repo($ruolo), $sanzioneId, $emittenteId)) {
            flash('ok', self::esitoRevoca($ruolo));
        } else {
            flash('warn', 'Sanzione non trovata, non di tua competenza o già revocata.');
        }

        redirect(self::rotta($ruolo));
    }

    /*
        * POST — aggiorna il periodo di una sanzione attiva dell'emittente.
        * Il parametro $id è opzionale, se non viene passato viene letto da POST.
        */
    public function aggiornaPeriodoSanzione(int|string $id = 0): void
    {
        [$emittenteId, $ruolo] = self::emittente();
        self::richiediPostConCsrf($ruolo);

        $sanzioneId = (int) ($id ?: post('sanzione_id', '0'));
        $dataFine   = trim((string) post('data_fine', ''));

        try {
            $n = FPersistentManager::sanzioneModificaPeriodo(
                self::repo($ruolo),
                $sanzioneId,
                $emittenteId,
                (string) post('tipo', ''),
                $dataFine !== '' ? $dataFine : null
            );
            flash('ok', 'Periodo del provvedimento aggiornato.' . self::codaModifica($ruolo, $n));
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
        } catch (Throwable) {
            flash('error', "Errore durante l'aggiornamento. Riprova tra qualche istante.");
        }

        redirect(self::rotta($ruolo));
    }

    // ---- Parti dipendenti dall'emittente (risolte in base al ruolo) ----

    /*     * Restituisce l'ID e il ruolo dell'emittente (gestore o noleggio).
     * L'emittente deve essere autenticato e avere il ruolo corretto.
     */
    private static function emittente(): array
    {
        CAuth::richiediRuolo([EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo]);
        $user = CAuth::utenteCorrente();

        return [(int) $user['id'], (string) $user['ruolo']];
    }

    /** Blocca le richieste non-POST o senza token valido (redirect alla sezione). */
    private static function richiediPostConCsrf(string $ruolo): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(self::rotta($ruolo));
        }
        if (!csrf_check(post('csrf_token'))) {
            flash('error', 'Token di sicurezza non valido. Ricarica la pagina e riprova.');
            redirect(self::rotta($ruolo));
        }
    }

    private static function isNoleggio(string $ruolo): bool
    {
        return $ruolo === EGestoreNoleggio::$ruolo;
    }

    /* Restituisce il nome della repository da usare per le sanzioni, in base al ruolo dell'emittente.
     */
    private static function repo(string $ruolo): string
    {
        return self::isNoleggio($ruolo)
            ? FPersistentManager::SANZIONE_PILOTA_NOLEGGIO_CLASS
            : FPersistentManager::SANZIONE_PILOTA_CLASS;
    }

    /*  Restituisce la rotta da usare per il redirect dopo un'azione, in base al ruolo dell'emittente.
     */
    private static function rotta(string $ruolo): string
    {
        return self::isNoleggio($ruolo) ? '/sanzioniNoleggio' : '/sanzioniGestore';
    }

    /** Frase del requisito pilota nella validazione. */
    private static function requisitoPilota(string $ruolo): string
    {
        return self::isNoleggio($ruolo)
            ? 'deve aver noleggiato un tuo veicolo'
            : 'deve aver prenotato su un tuo circuito';
    }

    /** Applica la sanzione via Foundation e chiude con esito o errori. */
    private static function eseguiApplicazione(int $emittenteId, string $ruolo, array $dati): void
    {
        $tipo = (string) $dati['tipo'];
        try {
            $annullate = FPersistentManager::sanzioneApplica(
                self::repo($ruolo),
                $emittenteId,
                (int) $dati['pilota_id'],
                $tipo,
                $tipo === ESanzionePilota::TIPO_SOSPENSIONE ? (string) $dati['data_fine'] : null,
                (string) ($dati['motivo'] ?? '')
            );
        } catch (InvalidArgumentException $e) {
            self::mostra($emittenteId, $ruolo, [$e->getMessage()], $dati);
            return;
        } catch (Throwable $e) {
            self::mostra($emittenteId, $ruolo, ['Errore durante il salvataggio: ' . $e->getMessage()], $dati);
            return;
        }

        flash('ok', self::esitoCrea($ruolo, $tipo === ESanzionePilota::TIPO_BAN ? 'Pilota bannato' : 'Pilota sospeso', $annullate));
        redirect(self::rotta($ruolo));
    }

    /** Messaggio di conferma applicazione sanzione. */
    private static function esitoCrea(string $ruolo, string $etichetta, int $annullate): string
    {
        if (self::isNoleggio($ruolo)) {
            $coda = $annullate > 0
                ? ' Annullati e rimborsati al 100% ' . $annullate
                    . ($annullate === 1 ? ' noleggio futuro.' : ' noleggi futuri.')
                : ' Nessun noleggio futuro da annullare.';

            return $etichetta . ' dai tuoi veicoli a noleggio.' . $coda;
        }

        $coda = $annullate > 0
            ? ' Annullate e rimborsate al 100% ' . $annullate
                . ($annullate === 1 ? ' prenotazione futura.' : ' prenotazioni future.')
            : ' Nessuna prenotazione futura da annullare.';

        return $etichetta . ' sui tuoi circuiti.' . $coda;
    }

    /** Messaggio di conferma revoca. */
    private static function esitoRevoca(string $ruolo): string
    {
        return self::isNoleggio($ruolo)
            ? 'Sanzione revocata. Il pilota può di nuovo noleggiare i tuoi veicoli.'
            : 'Sanzione revocata. Il pilota può di nuovo prenotare i tuoi circuiti.';
    }

    /** Coda del messaggio di modifica periodo. */
    private static function codaModifica(string $ruolo, int $n): string
    {
        if ($n < 1) {
            return '';
        }

        return self::isNoleggio($ruolo)
            ? ' Annullati e rimborsati al 100% ' . $n . ($n === 1 ? ' noleggio.' : ' noleggi.')
            : ' Annullate e rimborsate al 100% ' . $n . ($n === 1 ? ' prenotazione.' : ' prenotazioni.');
    }

    // ---- Macchina di controllo condivisa (helper privati) ----

    /**
     * Mostra la pagina di gestione dei ban e delle sospensioni, con eventuali errori e dati del form.
    */
    private static function mostra(int $emittenteId, string $ruolo, array $errors, array $form): void
    {
        $repo         = self::repo($ruolo);
        $sanzioni     = FPersistentManager::sanzioneLoadByGestore($repo, $emittenteId);
        $sanzionabili = FPersistentManager::sanzionePilotiSanzionabili($repo, $emittenteId);

        if (self::isNoleggio($ruolo)) {
            VSanzioniNoleggio::elenco($sanzioni, $sanzionabili, $errors, $form);
            return;
        }

        VSanzioniGestore::elenco($sanzioni, $sanzionabili, $errors, $form);
    }

     /**
     * Valida i dati del form e restituisce un array di errori (vuoto se tutto ok).
     */
    private static function valida(array $dati, int $emittenteId, string $ruolo): array
    {
        return array_merge(
            self::erroriPilota($dati, $emittenteId, $ruolo),
            self::erroriTipoEPeriodo($dati),
            self::erroriMotivo($dati)
        );
    }

    /* Controlla che il pilota sia selezionato e sanzionabile.
     * Restituisce un array di errori (vuoto se tutto ok).
     */
    private static function erroriPilota(array $dati, int $emittenteId, string $ruolo): array
    {
        $pilotaId = (int) ($dati['pilota_id'] ?? 0);
        if ($pilotaId < 1) {
            return ['Seleziona il pilota da sanzionare.'];
        }
        if (!FPersistentManager::sanzionePilotaSanzionabile(self::repo($ruolo), $emittenteId, $pilotaId)) {
            return ['Il pilota selezionato non è valido: ' . self::requisitoPilota($ruolo)
                . ' e non avere già una sanzione attiva.'];
        }

        return [];
    }

    /* Controlla che il tipo sia valido e, se sospensione, che la data di fine sia valida.
     * Restituisce un array di errori (vuoto se tutto ok).
     */
    private static function erroriTipoEPeriodo(array $dati): array
    {
        $tipo = (string) ($dati['tipo'] ?? '');
        if (!in_array($tipo, ESanzionePilota::TIPI, true)) {
            return ['Seleziona il tipo di provvedimento (ban o sospensione).'];
        }
        if ($tipo !== ESanzionePilota::TIPO_SOSPENSIONE) {
            return [];
        }

        $dataFine = trim((string) ($dati['data_fine'] ?? ''));
        if ($dataFine === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFine)) {
            return ['Per la sospensione indica una data di fine valida.'];
        }
        try {
            return new DateTimeImmutable($dataFine) < new DateTimeImmutable('today')
                ? ['La fine della sospensione non può essere nel passato.']
                : [];
        } catch (Exception) {
            return ['Data di fine sospensione non valida.'];
        }
    }

    /* Controlla che la motivazione sia presente e non troppo lunga.
     * Restituisce un array di errori (vuoto se tutto ok).
     */
    private static function erroriMotivo(array $dati): array
    {
        $motivo = trim((string) ($dati['motivo'] ?? ''));
        if ($motivo === '') {
            return ['Indica la motivazione del provvedimento.'];
        }
        if (mb_strlen($motivo) > 255) {
            return ['La motivazione non può superare 255 caratteri.'];
        }

        return [];
    }

    /* Restituisce i dati del form letti da POST, con valori di default.
     */
    private static function datiDaPost(): array
    {
        return [
            'csrf_token' => (string) post('csrf_token', ''),
            'pilota_id'  => (int) post('pilota_id', '0'),
            'tipo'       => (string) post('tipo', ''),
            'data_fine'  => (string) post('data_fine', ''),
            'motivo'     => (string) post('motivo', ''),
        ];
    }
}