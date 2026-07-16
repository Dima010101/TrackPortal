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
        $pilotaId = (int) ($_GET['pilota_id'] ?? 0);
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