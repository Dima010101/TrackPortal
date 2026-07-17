<?php

/**
 * Repository per le sanzioni emesse dai gestori verso i piloti.
 */
class FSanzionePilota extends FRepository
{
    protected static function entityClass(): string
    {
        return ESanzionePilota::class;
    }

    /**
     * Tipo di emittente della sanzione (gestore circuito ).
     */ 
    protected static function emittenteTipo(): string
    {
        return ESanzionePilota::EMITTENTE_GESTORE;
    }

    /**
     * Restituisce la sanzione attiva (operativa) che blocca il pilota su un circuito, se esiste.
     */
    public static function pilotaBloccatoSuCircuito(int $pilotaId, int $circuitoId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT s.*
             FROM sanzione_pilota s
             JOIN circuito c ON c.gestore_id = s.gestore_id
             WHERE c.id = :circuito
               AND s.pilota_id = :pilota
               AND s.stato = 'attiva'
               AND s.data_inizio <= CURDATE()
               AND (s.tipo = 'ban' OR s.data_fine >= CURDATE())
             ORDER BY (s.tipo = 'ban') DESC, s.data_fine DESC
             LIMIT 1",
            [':circuito' => $circuitoId, ':pilota' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /**
     * Restituisce la sanzione attiva (operativa) che blocca il pilota da parte del gestore, se esiste.
     */
    public static function loadAttivaByGestorePilota(int $gestoreId, int $pilotaId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT *
             FROM sanzione_pilota
             WHERE gestore_id = :g
               AND pilota_id = :p
               AND stato = 'attiva'
               AND (tipo = 'ban' OR data_fine >= CURDATE())
             ORDER BY (tipo = 'ban') DESC, data_fine DESC
             LIMIT 1",
            [':g' => $gestoreId, ':p' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /**
     * Restituisce tutte le sanzioni emesse dal gestore verso i piloti, con i dati del pilota.
     */
    public static function loadByGestore(int $gestoreId): array
    {
        $rows = FDataBase::executeQuery(
            "SELECT s.*,
                    u.nome AS pilota_nome, u.cognome AS pilota_cognome, u.email AS pilota_email
             FROM sanzione_pilota s
             JOIN account u ON u.id = s.pilota_id
             WHERE s.gestore_id = :g
             ORDER BY (s.stato = 'attiva') DESC, s.data_creazione DESC, s.id DESC",
            [':g' => $gestoreId]
        )->fetchAllAssociative();

        $oggi = (new DateTimeImmutable('today'))->format('Y-m-d');
        foreach ($rows as &$r) {
            $r['stato_effettivo'] = self::statoEffettivo($r, $oggi);
        }
        unset($r);

        return $rows;
    }

    /**
     * Restituisce l'elenco dei piloti che hanno prenotato su un circuito del gestore e che non hanno già una sanzione operativa in corso.
     */
    public static function pilotiSanzionabili(int $gestoreId): array
    {
        return FDataBase::executeQuery(
            "SELECT DISTINCT u.id, u.nome, u.cognome, u.email
             FROM prenotazione p
             JOIN circuito c ON c.id = p.circuito_id
             JOIN account u ON u.id = p.pilota_id
             WHERE c.gestore_id = :g
               AND NOT EXISTS (
                   SELECT 1 FROM sanzione_pilota s
                   WHERE s.gestore_id = :g2
                     AND s.pilota_id = u.id
                     AND s.stato = 'attiva'
                     AND (s.tipo = 'ban' OR s.data_fine >= CURDATE())
               )
             ORDER BY u.cognome, u.nome",
            [':g' => $gestoreId, ':g2' => $gestoreId]
        )->fetchAllAssociative();
    }

    /**
     * True se il pilota può essere sanzionato dal gestore: ha prenotato su un suo
     * circuito e non ha già una sanzione operativa in corso.
     */
    public static function pilotaSanzionabile(int $gestoreId, int $pilotaId): bool
    {
        $haPrenotato = (int) FDataBase::executeQuery(
            "SELECT COUNT(*)
             FROM prenotazione p
             JOIN circuito c ON c.id = p.circuito_id
             WHERE c.gestore_id = :g AND p.pilota_id = :p",
            [':g' => $gestoreId, ':p' => $pilotaId]
        )->fetchOne();

        if ($haPrenotato < 1) {
            return false;
        }

        return self::loadAttivaByGestorePilota($gestoreId, $pilotaId) === null;
    }