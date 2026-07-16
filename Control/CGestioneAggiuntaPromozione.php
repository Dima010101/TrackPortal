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

    /*
    * Suddivide le promozioni create dall'utente in due gruppi (attive e passate)
    * in base allo stato attuale e alla data di scadenza.
    *
    * Ritorna un array contenente la lista delle attive e la lista delle passate.
    */
    private static function riepilogoPromozioni(int $accountId): array
    {
        $oggi    = (new DateTimeImmutable('today'))->format('Y-m-d');
        $attive  = [];
        $passate = [];

        foreach (FPersistentManager::promozioneLoadByCreatore($accountId) as $p) {
            $dataFine = trim((string) ($p['data_fine'] ?? ''));
            $scaduta  = $dataFine !== '' && $dataFine < $oggi;
            if (($p['stato_promozione'] ?? 'attiva') !== 'attiva' || $scaduta) {
                $passate[] = $p;
            } else {
                $attive[] = $p;
            }
        }

        return [$attive, $passate];
    }

    /*
    * Verifica che l'utente corrente sia un gestore autorizzato (circuiti o noleggio).
    *
    * Ritorna un array contenente l'ID dell'account e il rispettivo ruolo.
    */
    private static function richiediGestorePromozioni(): array
    {
        CAuth::richiediRuolo([EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo]);
        $user = CAuth::utenteCorrente();

        return [(int) $user['id'], (string) $user['ruolo']];
    }
    
    /*
    * Associa il ruolo dell'utente al corrispondente tipo di entità 
    * (veicolo per il gestore noleggio, circuito negli altri casi).
    */
    private static function tipoEntitaPerRuolo(string $ruolo): string
    {
        return $ruolo === EGestoreNoleggio::$ruolo
            ? self::TIPO_ENTITA_VEICOLO
            : self::TIPO_ENTITA_CIRCUITO;
    }

    /*
    * Carica tutte le entità (veicoli a noleggio o circuiti) gestite dall'utente 
    * in base al suo ruolo.
    */
    private static function caricaEntitaGestite(int $accountId, string $ruolo): array
    {
        if ($ruolo === EGestoreNoleggio::$ruolo) {
            return FPersistentManager::veicoloNoleggioLoadByAzienda($accountId);
        }

        return FPersistentManager::circuitoLoadByGestore($accountId);
    }

    /*
    * Verifica che l'entità selezionata appartenga effettivamente all'utente connesso
    * per prevenire accessi non autorizzati (controllo di sicurezza anti-IDOR).
    *
    * Ritorna i dati dell'entità se appartiene all'utente, altrimenti null.
    */
    private static function verificaOwnershipEntita(int $entitaId, int $accountId, string $ruolo): ?array
    {
        if ($entitaId < 1) {
            return null;
        }
        if ($ruolo === EGestoreNoleggio::$ruolo) {
            return FPersistentManager::veicoloNoleggioLoadByIdAndAzienda($entitaId, $accountId);
        }

        $circuito = FPersistentManager::circuitoLoadById($entitaId);
        if ($circuito === null || (int) ($circuito['gestore_id'] ?? 0) !== $accountId) {
            return null;
        }

        return $circuito;
    }

    /*
    * Restituisce i dati dell'entità se appartiene all'utente connesso.
    * Se l'entità non viene trovata o l'utente non è autorizzato, imposta un messaggio d'errore
    * e reindirizza alla pagina principale delle promozioni.
    */
    private static function entitaOppureRedirect(int $entitaId, int $accountId, string $ruolo): array
    {
        $entita = self::verificaOwnershipEntita($entitaId, $accountId, $ruolo);
        if ($entita === null) {
            flash('error', 'Risorsa non trovata o non autorizzata.');
            redirect('/promozioni');
        }

        return $entita;
    }

    /*
    * Recupera una specifica promozione e la relativa entità associata, verificandone la proprietà.
    * Se la promozione o l'entità non esistono o non sono autorizzate, mostra un errore e
    * reindirizza alla pagina principale delle promozioni.
    */
    private static function promozioneConEntitaOppureRedirect(int $promoId, int $accountId, string $ruolo): array
    {
        $promo = $promoId > 0 ? FPersistentManager::promozioneFindOwned($promoId, $accountId) : null;
        if ($promo === null) {
            flash('error', 'Promozione non trovata o non autorizzata.');
            redirect('/promozioni');
        }

        $entitaId = self::entitaIdDaPromozione($promo, $ruolo);

        return [$promo, self::entitaOppureRedirect($entitaId, $accountId, $ruolo), $entitaId];
    }

    /*
    * Recupera tutte le promozioni associate a una specifica entità 
    * (tramite veicolo o circuito a seconda del ruolo).
    */
    private static function caricaPromozioniPerEntita(int $entitaId, string $ruolo): array
    {
        if ($ruolo === EGestoreNoleggio::$ruolo) {
            return FPersistentManager::promozioneLoadByVeicolo($entitaId);
        }

        return FPersistentManager::promozioneLoadByCircuitoConDettagli($entitaId);
    }

    /*
    * Recupera l'ID dell'entità (circuito o veicolo) a cui è collegata la promozione.
    */
    private static function entitaIdDaPromozione(EPromozione $promo, string $ruolo): int
    {
        return $ruolo === EGestoreNoleggio::$ruolo
            ? (int) ($promo->getVeicoloNoleggioId() ?? 0)
            : (int) ($promo->getCircuitoId() ?? 0);
    }

    /*
    * Mostra la schermata con il modulo per l'inserimento di una nuova promozione,
    * passando alla vista eventuali dati precompilati ed errori di validazione.
    */
    private static function mostraFormNuova(array $entita, int $entitaId, string $ruolo, array $form, array $errors): void
    {
        VPromozioni::formAggiungiPromozione(
            $entita,
            $entitaId,
            self::tipoEntitaPerRuolo($ruolo),
            $ruolo,
            self::DASH_BACK,
            $form,
            $errors
        );
    }
}