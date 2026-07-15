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