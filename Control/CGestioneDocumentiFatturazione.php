<?php

/**
 * Gestione dei template di fatturazione (PDF) e delle ultime prenotazioni.
 */
class CGestioneDocumentiFatturazione
{
    /** Azione «landing» invocata dall'URL «pulita» di sezione (senza azione). */
    public const DEFAULT_ACTION = 'gestisciTemplateFatture';

    /* Lista delle rotte gestite da questo controller. */
    public const ROUTES = [
        'POST carica' => 'caricaTemplateFattura',
    ];

    /* Mostra la pagina di gestione dei template fatture */
    public function gestisciTemplateFatture(): void
    {
        self::richiediAdmin();

        VAmministratore::fatture(
            FPersistentManager::prenotazioneLoadUltimePerFatturazione(20),
            self::datiTemplateFattura( self::vistaRichiesta((string) get('vista', '')))
        );
    }

    /**
     * Carica un nuovo template di fattura (PDF) per la vista richiesta.
     */
    public function caricaTemplateFattura(array|null $file_template = null): void
    {
        self::richiediAdmin();
        $user  = CAuth::utenteCorrente();
        $vista = self::vistaRichiesta((string) post('vista', ''));

        $errors = self::erroriRichiestaUpload();
        if ($errors !== []) {
            self::reindirizzaConErrori($vista, $errors);
            return;
        }

        self::salvaTemplate($vista, $file_template ?? ($_FILES['file_template'] ?? []), (int) $user['id']);
    }

    /* Restituisce i dati del template di fattura per la vista richiesta.
     */
    private static function vistaRichiesta(string $vista): string
    {
        return FPersistentManager::templateFatturaNormalizzaVista(
            $vista !== '' ? $vista : FPersistentManager::TEMPLATE_FATTURA_VISTA_PILOTA
        );
    }

    /* Restituisce un array di errori se la richiesta di upload non è valida.
     * Altrimenti restituisce un array vuoto.
     */
    private static function erroriRichiestaUpload(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Richiesta non valida: invia il form di upload per aggiornare il template.'];
        }
        if (!csrf_check(post('csrf_token'))) {
            return ['Token CSRF non valido. Ricarica la pagina e riprova.'];
        }

        return [];
    }

    /* Salva il template di fattura per la vista richiesta.
     * In caso di errore, reindirizza alla pagina di gestione con un messaggio flash.
     */
    private static function salvaTemplate(string $vista, array $file, int $adminId): void
    {
        try {
            FPersistentManager::templateFatturaSalvaUpload($vista, $file, $adminId);
            flash(
                'ok',
                'Template aggiornato per «' . FPersistentManager::templateFatturaEtichettaVista($vista)
                    . '». Le prossime PDF useranno il nuovo layout.'
            );
            redirect(self::urlSezioneTemplate($vista));
        } catch (InvalidArgumentException | RuntimeException $e) {
            self::reindirizzaConErrori($vista, [$e->getMessage()]);
        } catch (Throwable) {
            self::reindirizzaConErrori(
                $vista,
                ['Errore imprevisto durante il salvataggio del template. Riprova tra qualche istante.']
            );
        }
    }

    /**
     * Restituisce i dati del template di fattura per la vista richiesta, con eventuali errori
     * da mostrare in pagina.
     */
    private static function datiTemplateFattura(string $vista): array
    {
        $errors = $_SESSION['_template_errors'] ?? [];
        unset($_SESSION['_template_errors']);

        [$meta, $anteprima] = self::metaEAnteprima($vista, $errors);

        return [
            'vista'      => $vista,
            'etichetta'  => FPersistentManager::templateFatturaEtichettaVista($vista),
            'is_fattura' => FPersistentManager::templateFatturaIsVistaFattura($vista),
            'meta'       => $meta,
            'anteprima'  => $anteprima,
            'errors'     => $errors,
            'viste'      => FPersistentManager::templateFatturaDescrizioneViste(),
        ];
    }

    /**
     * Restituisce i metadati e l'anteprima del template di fattura per la vista richiesta.
     * In caso di errore, aggiunge un messaggio all'array $errors.
     */
    private static function metaEAnteprima(string $vista, array &$errors): array
    {
        try {
            $meta = FPersistentManager::templateFatturaCaricaAttivo($vista);
        } catch (Throwable) {
            $errors[] = 'Impossibile caricare le informazioni sul template.';

            return [[], null];
        }

        try {
            return [$meta, FPersistentManager::templateFatturaAnteprimaRender($vista)];
        } catch (Throwable $e) {
            $errors[] = 'Anteprima non disponibile: ' . $e->getMessage();

            return [$meta, null];
        }
    }

    private static function urlSezioneTemplate(string $vista): string
    {
        return '/fatture?vista=' . rawurlencode($vista) . '#template-fattura';
    }

    private static function richiediAdmin(): void
    {
        CAuth::richiediRuolo(EAmministratore::$ruolo);
    }

    /* Reindirizza alla pagina di gestione dei template con un messaggio flash di errore.
     * I messaggi di errore vengono salvati in sessione e mostrati nella pagina di destinazione.
     */
    private static function reindirizzaConErrori(string $vista, array $errors): void
    {
        $_SESSION['_template_errors'] = $errors;
        redirect(self::urlSezioneTemplate($vista));
    }
}