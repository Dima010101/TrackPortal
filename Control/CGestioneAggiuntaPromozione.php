<?php

/*
 * CGestioneAggiuntaPromozioni — Classe di controllo del controller (Controller)
 * per il caso d'uso «Gestione e aggiunta promozioni».
 *
 * Gestisce l'intero ciclo di vita delle promozioni (visualizzazione, creazione,
 * modifica ed eliminazione) per i gestori dei circuiti e del noleggio.
 */
class CGestioneAggiuntaPromozioni
{
    
    public const DEFAULT_ACTION = 'gestisciPromozioni';

    //*redirect URL -> metodo 
    public const ROUTES = [
        'GET servizio/{id}' => 'elencaPromozioniEntita',
        'GET servizio/{id}/nuova' => 'nuovaPromozione',
        'POST servizio/{id}/nuova' => 'creaPromozione',
        'GET {id}/modifica' => 'modificaPromozione',
        'POST {id}/modifica' => 'aggiornaPromozione',
        'POST {id}/elimina' => 'eliminaPromozione',
    ];

    private const TIPO_ENTITA_CIRCUITO = 'circuito';
    private const TIPO_ENTITA_VEICOLO  = 'veicolo';

    private const DASH_BACK = '/dashboard';

    /** * Mostra la schermata principale di gestione, con l'elenco delle entità 
    * gestite (circuiti o veicoli) e il riepilogo delle promozioni attive e passate.
    */
    public function gestisciPromozioni(): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();

        $canCreateCircuiti = $ruolo !== EGestoreCircuiti::$ruolo
            || FPersistentManager::gestoreCircuitiIsAffiliazioneApprovata($accountId);
        [$promoAttive, $promoPassate] = self::riepilogoPromozioni($accountId);

        VPromozioni::selezioneEntita(
            self::caricaEntitaGestite($accountId, $ruolo),
            self::tipoEntitaPerRuolo($ruolo),
            $ruolo,
            self::DASH_BACK,
            $canCreateCircuiti,
            $promoAttive,
            $promoPassate
        );
    }

    //*Mostra l'elenco delle promozioni associate a una specifica entità selezionata (circuito o veicolo).
    public function elencaPromozioniEntita(int|string $id_entita = 0): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        $entitaId = (int) $id_entita;
        $entita   = self::entitaOppureRedirect($entitaId, $accountId, $ruolo);

        VPromozioni::elencoPromozioni(
            self::caricaPromozioniPerEntita($entitaId, $ruolo),
            $entita,
            $entitaId,
            self::tipoEntitaPerRuolo($ruolo),
            $ruolo,
            self::DASH_BACK
        );
    }

     /*
    * Mostra il modulo per la creazione di una nuova promozione legata all'entità selezionata.
    */
    public function nuovaPromozione(int|string $id_entita = 0): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        $entitaId = (int) $id_entita;
        $entita   = self::entitaOppureRedirect($entitaId, $accountId, $ruolo);

        self::mostraFormNuova($entita, $entitaId, $ruolo, [], []);
    }

    /*
    * Valida i dati inseriti e salva la nuova promozione nel sistema.
    * Se ci sono errori di validazione o di salvataggio, mostra nuovamente il modulo con i dettagli del problema.
    */
    public function creaPromozione(int|string $id_entita = 0, array $dati_promozione = []): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        $entitaId = (int) $id_entita;
        $entita   = self::entitaOppureRedirect($entitaId, $accountId, $ruolo);

        $dati_promozione = $dati_promozione !== [] ? $dati_promozione : self::datiPromozioneDaPost();
        $errors = !csrf_check(isset($dati_promozione['csrf_token']) ? (string) $dati_promozione['csrf_token'] : null)
            ? ['Token CSRF non valido.']
            : self::validaDatiPromozione($dati_promozione);
        if ($errors !== []) {
            self::mostraFormNuova($entita, $entitaId, $ruolo, $dati_promozione, $errors);
            return;
        }

        try {
            self::salvaENotifica($accountId, $ruolo, $entitaId, $entita, $dati_promozione);
        } catch (Throwable $ex) {
            self::mostraFormNuova($entita, $entitaId, $ruolo, $dati_promozione, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

    /*
    * Mostra il modulo di modifica per una promozione esistente,
    * precompilando i campi con i dati attuali.
    */
    public function modificaPromozione(int|string $id_promozione = 0): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        [$promo, $entita, $entitaId] = self::promozioneConEntitaOppureRedirect((int) $id_promozione, $accountId, $ruolo);

        self::mostraFormModifica($promo, $entita, $entitaId, $ruolo, self::promoToForm($promo), []);
    }

    /*
    * Valida i dati modificati e aggiorna la promozione nel sistema.
    * Se l'operazione va a buon fine, reindirizza alla pagina dell'entità, 
    * altrimenti mostra nuovamente il modulo con i dettagli dell'errore.
    */
    public function aggiornaPromozione(int|string $id_promozione = 0, array $dati_promozione = []): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        [$promo, $entita, $entitaId] = self::promozioneConEntitaOppureRedirect((int) $id_promozione, $accountId, $ruolo);
        $dati_promozione = $dati_promozione !== [] ? $dati_promozione : self::datiPromozioneDaPost();

        $errors = self::erroriRichiestaModifica($dati_promozione);
        if ($errors !== []) {
            self::mostraFormModifica($promo, $entita, $entitaId, $ruolo, $dati_promozione, $errors);
            return;
        }

        try {
            self::applicaModifichePromozione($promo, $dati_promozione, $entitaId, $ruolo);
            flash('ok', 'Promozione aggiornata.');
            redirect('/promozioni/servizio/' . $entitaId);
        } catch (Throwable $ex) {
            self::mostraFormModifica($promo, $entita, $entitaId, $ruolo, $dati_promozione, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

    /*
    * Elimina in modo definitivo una promozione esistente.
    * Se l'operazione ha successo o se si verificano errori, l'utente viene 
    * reindirizzato alla pagina dell'entità con un messaggio di conferma o di errore.
    */
    public function eliminaPromozione(int|string $id_promozione = 0): void
    {
        [$accountId, $ruolo] = self::richiediGestorePromozioni();
        $promoId = (int) $id_promozione;
        $promo   = $promoId > 0 ? FPersistentManager::promozioneFindOwned($promoId, $accountId) : null;

        $errors = self::erroriEliminazione($promo);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect($promo !== null
                ? '/promozioni/servizio/' . self::entitaIdDaPromozione($promo, $ruolo)
                : '/promozioni');
        }

        $entitaId = self::entitaIdDaPromozione($promo, $ruolo);
        try {
            FPersistentManager::promozioneDeleteOwned($promo);
            flash('ok', 'Promozione eliminata.');
        } catch (Throwable) {
            flash('error', "Errore durante l'eliminazione della promozione. Riprova tra qualche istante.");
        }

        redirect('/promozioni/servizio/' . $entitaId);
    }