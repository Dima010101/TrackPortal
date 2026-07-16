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