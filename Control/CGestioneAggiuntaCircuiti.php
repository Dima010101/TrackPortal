<?php

/**
 * caso d'uso Gestione e aggiunta circuiti (uc 8).
 *
 * Operazioni di sistema:
 *  - elencaCircuitiGestiti() → 1a/1b: elenco «I miei circuiti»
 *  - nuovoCircuito()         → 2a/2b: form di aggiunta circuito
 *  - creaCircuito()          → 3a/3b: validazione e conferma dell'aggiunta
 *  - modificaCircuito()      → gestione: form di modifica precompilato
 *  - aggiornaCircuito()      → gestione: salvataggio delle modifiche*/

class CGestioneAggiuntaCircuiti
{
    /** Azione base invocata dall'URL di sezione (senza azione). */
    public const DEFAULT_ACTION = 'elencaCircuitiGestiti';

    /** Tabella URL → metodo*/
    public const ROUTES = [
        'GET nuovo' => 'nuovoCircuito',
        'POST nuovo' => 'creaCircuito',
        'GET {id}/modifica' => 'modificaCircuito',
        'POST {id}/modifica' => 'aggiornaCircuito',
    ];

    private const TIPOLOGIE_VEICOLI = ['entrambi', 'auto', 'moto'];

    /**elenco circuiti gestiti dall'utente loggato */
    public function elencaCircuitiGestiti(): void
    {
        $gestoreId = self::richiediGestoreCircuiti();

        VGestoreCircuiti::mieiCircuiti(
            FPersistentManager::circuitoLoadByGestore($gestoreId),
            FPersistentManager::gestoreCircuitiIsAffiliazioneApprovata($gestoreId),
            FPersistentManager::gestoreCircuitiGetAffiliazione($gestoreId)
        );
    }

    /** form vuoto per l'inserimento di un nuovo circuito (2a/2b). */
    public function nuovoCircuito(): void
    {
        $gestoreId = self::richiediGestoreCircuiti();
        self::richiediAffiliazioneApprovata($gestoreId);

        VGestoreCircuiti::formAggiungiCircuito();
    }

    /**
     * valida i dati, salva il circuito e mostra conferma o errori (3a/3b)*/
    public function creaCircuito(array $datiCircuito = []): void
    {
        $gestoreId = self::richiediGestoreCircuiti();
        self::richiediAffiliazioneApprovata($gestoreId);

        $datiCircuito = $datiCircuito !== [] ? $datiCircuito : self::datiCircuitoDaPost();
        $fotoUploads  = self::fotoUploadsDaRequest();
        $datiCircuito['foto_didascalie'] = self::didascalieDaUploads($fotoUploads);

        $errors = self::erroriRichiesta($datiCircuito, $fotoUploads);
        if ($errors !== []) {
            VGestoreCircuiti::formAggiungiCircuito($datiCircuito, $errors);
            return;
        }

        try {
            VGestoreCircuiti::confermaAggiuntaCircuito(self::salvaNuovoCircuito($gestoreId, $datiCircuito, $fotoUploads));
        } catch (Throwable $ex) {
            VGestoreCircuiti::formAggiungiCircuito($datiCircuito, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

    /** modifica di un circuito esistente del gestore. */
    public function modificaCircuito(int|string $id = 0): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) $id;

        if (!FPersistentManager::circuitoIsDelGestore($circuitoId, $gestoreId)) {
            flash('error', 'Circuito non trovato o non di tua proprietà.');
            redirect('/circuitiGestore');
        }

        $row = FPersistentManager::circuitoLoadById($circuitoId) ?? [];
        VGestoreCircuiti::formModificaCircuito($circuitoId, self::formDaRow($row), $row['foto'] ?? []);
    }

    /**conferma le modifiche apportate ad un circuito esistente */
    public function aggiornaCircuito(int|string $id = 0, array $datiCircuito = []): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) $id;
        $circuito   = self::circuitoDelGestoreOppureRedirect($circuitoId, $gestoreId);

        $datiCircuito = $datiCircuito !== [] ? $datiCircuito : self::datiCircuitoDaPost();
        $fotoUploads  = self::fotoUploadsDaRequest();
        $idsRimuovi   = self::idsFotoDaRimuovere();
        $datiCircuito['foto_didascalie'] = self::didascalieDaUploads($fotoUploads);

        $errors = self::erroriRichiesta($datiCircuito, $fotoUploads, max(0, $circuito->contaFoto() - count($idsRimuovi)));
        if ($errors !== []) {
            self::mostraFormModifica($circuitoId, $datiCircuito, $errors);
            return;
        }

        try {
            self::salvaModificheCircuito($circuito, $datiCircuito, $idsRimuovi, $fotoUploads);
            flash('ok', 'Circuito aggiornato correttamente.');
            redirect('/circuiti/' . $circuitoId);
        } catch (Throwable $ex) {
            self::mostraFormModifica($circuitoId, $datiCircuito, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

}