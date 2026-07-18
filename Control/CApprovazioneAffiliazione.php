<?php

// Classe di controllo per l'approvazione delle affiliazioni
class CApprovazioneAffiliazione
{
    // Azione di default della sezione
    public const DEFAULT_ACTION = 'elencaRichiesteAffiliazione';

    // Rotte risolte da CFrontController
    public const ROUTES = [
        'GET {id}' => 'visualizzaRichiestaAffiliazione',
        'POST {id}/approva' => 'approvaRichiesta',
        'POST {id}/respingi' => 'respingiRichiesta',
    ];

    // Elenco richieste in sospeso e storico
    public function elencaRichiesteAffiliazione(): void
    {
        self::richiediAmministratore();

        VAffiliazioni::richiestePending(
            FPersistentManager::affiliazioneLoadPendingRichieste(),
            FPersistentManager::affiliazioneLoadStoricoRichieste()
        );
    }

    // Dettaglio di una richiesta
    public function visualizzaRichiestaAffiliazione(int|string $idRichiesta = 0): void
    {
        self::richiediAmministratore();

        $richiesta = FPersistentManager::affiliazioneLoadRichiestaById((int) $idRichiesta);
        if ($richiesta === null) {
            flash('error', 'Richiesta di affiliazione non trovata.');
            redirect('/affiliazioni');
        }

        VAffiliazioni::dettaglioRichiesta($richiesta);
    }

    // Approva una richiesta ancora in sospeso
    public function approvaRichiesta(int|string $idRichiesta = 0): void
    {
        self::richiediAmministratore();

        $id   = (int) $idRichiesta;
        $tipo = (string) post('tipo', '');

        $errors = self::validaRichiestaPost($id, $tipo);
        if ($errors !== []) {
            self::mostraErrori($id, $errors);
            return;
        }

        $esito = FPersistentManager::affiliazioneApprovaSeInAttesa($id, $tipo);
        if (!$esito['ok']) {
            self::mostraEsitoFallito($id, $esito);
            return;
        }

        self::notificaEsitoPilota($tipo, $esito['richiesta'] ?? [], true);
        VAffiliazioni::confermaApprovazione($esito['richiesta'] ?? []);
    }

    // Respinge una richiesta ancora in sospeso
    public function respingiRichiesta(int|string $idRichiesta = 0): void
    {
        self::richiediAmministratore();

        $id     = (int) $idRichiesta;
        $tipo   = (string) post('tipo', '');
        $motivo = trim((string) post('motivo', ''));

        $errors = self::validaRichiestaPost($id, $tipo);
        if ($errors !== []) {
            self::mostraErrori($id, $errors, $motivo);
            return;
        }

        $esito = FPersistentManager::affiliazioneRespingiSeInAttesa($id, $tipo);
        if (!$esito['ok']) {
            self::mostraEsitoFallito($id, $esito, $motivo);
            return;
        }

        self::notificaEsitoPilota($tipo, $esito['richiesta'] ?? [], false);
        VAffiliazioni::confermaRifiuto($esito['richiesta'] ?? [], $motivo);
    }

    private static function richiediAmministratore(): void
    {
        CAuth::richiediRuolo(EAmministratore::$ruolo);
    }

    private static function validaRichiestaPost(int $id, string $tipo): array
    {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = 'Richiesta non valida: usa il form per confermare l\'operazione.';
        } elseif (!csrf_check(post('csrf_token'))) {
            $errors[] = 'Token CSRF non valido. Ricarica la pagina e riprova.';
        }

        if ($id < 1) {
            $errors[] = 'Identificativo richiesta non valido.';
        }
        if (!in_array($tipo, [EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo, EPilota::$ruolo], true)) {
            $errors[] = 'Tipo di richiesta non valido.';
        }

        return $errors;
    }

    // Ripropone il dettaglio della richiesta con gli errori
    private static function mostraErrori(int $id, array $errors, string $motivo = ''): void
    {
        $richiesta = FPersistentManager::affiliazioneLoadRichiestaById($id);
        if ($richiesta !== null) {
            VAffiliazioni::dettaglioRichiesta($richiesta, $errors, $motivo);
            return;
        }

        VAffiliazioni::esitoOperazione(false, $errors);
    }

    // Esito negativo della Foundation
    private static function mostraEsitoFallito(int $id, array $esito, string $motivo = ''): void
    {
        $errore    = [$esito['error'] ?? 'Operazione non consentita.'];
        $richiesta = $esito['richiesta'] ?? FPersistentManager::affiliazioneLoadRichiestaById($id);

        if ($richiesta !== null) {
            VAffiliazioni::dettaglioRichiesta($richiesta, $errore, $motivo);
            return;
        }

        VAffiliazioni::esitoOperazione(false, $errore);
    }

    // Email al pilota sull'esito della convalida documenti
    private static function notificaEsitoPilota(string $tipo, array $richiesta, bool $approvata): void
    {
        if ($tipo !== EPilota::$ruolo || $richiesta === []) {
            return;
        }

        try {
            $approvata
                ? FPersistentManager::notificheDocumentiPilotaApprovati($richiesta)
                : FPersistentManager::notificheDocumentiPilotaRespinti($richiesta);
        } catch (Throwable $e) {
            error_log('Notifica documenti pilota: ' . $e->getMessage());
        }
    }
}
