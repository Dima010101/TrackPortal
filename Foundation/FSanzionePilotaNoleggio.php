<?php

/**
 * Repository delle sanzioni pilota (ban / sospensione) emesse da un'AZIENDA DI NOLEGGIO.           
 */
class FSanzionePilotaNoleggio extends FSanzionePilota
{
    protected static function emittenteTipo(): string
    {
        return ESanzionePilota::EMITTENTE_AZIENDA;
    }

    /**
     * Verifica se un pilota è bloccato da un'azienda di noleggio.
     */
    public static function pilotaBloccatoSuAzienda(int $pilotaId, int $aziendaId): ?array
    {
        return self::loadAttivaByGestorePilota($aziendaId, $pilotaId);
    }

    /**
     * Restituisce l'elenco dei piloti che hanno effettuato prenotazioni con i veicoli di un'azienda di noleggio
     * e che non sono attualmente sanzionati (ban o sospensione) da quell'azienda.
     */
    public static function pilotiSanzionabili(int $gestoreId): array
    {
        return FDataBase::executeQuery(
            "SELECT DISTINCT u.id, u.nome, u.cognome, u.email
             FROM prenotazione p
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             JOIN account u ON u.id = p.pilota_id
             WHERE v.azienda_id = :a
               AND NOT EXISTS (
                   SELECT 1 FROM sanzione_pilota s
                   WHERE s.gestore_id = :a2
                     AND s.pilota_id = u.id
                     AND s.stato = 'attiva'
                     AND (s.tipo = 'ban' OR s.data_fine >= CURDATE())
               )
             ORDER BY u.cognome, u.nome",
            [':a' => $gestoreId, ':a2' => $gestoreId]
        )->fetchAllAssociative();
    }