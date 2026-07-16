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
            self::datiTemplateFattura(self::vistaRichiesta((string) ($_GET['vista'] ?? '')))
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