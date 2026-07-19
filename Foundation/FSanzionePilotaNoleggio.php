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

     /**
     * Verifica se un pilota ha effettuato almeno una prenotazione con i veicoli di un'azienda di noleggio
     * e non è attualmente sanzionato (ban o sospensione) da quell'azienda.
     */
    public static function pilotaSanzionabile(int $gestoreId, int $pilotaId): bool
    {
        $haNoleggiato = (int) FDataBase::executeQuery(
            "SELECT COUNT(*)
             FROM prenotazione p
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE v.azienda_id = :a AND p.pilota_id = :p",
            [':a' => $gestoreId, ':p' => $pilotaId]
        )->fetchOne();

        if ($haNoleggiato < 1) {
            return false;
        }

        return self::loadAttivaByGestorePilota($gestoreId, $pilotaId) === null;
    }

    /** Testo della causa di annullamento per ban (permanente) o sospensione (con scadenza). */
    protected static function causaStorno(string $tipo, ?string $dataFineNorm): string
    {
        return $tipo === ESanzionePilota::TIPO_BAN
            ? 'Prenotazione annullata: ban del pilota dall\'azienda di noleggio.'
            : 'Prenotazione annullata: sospensione del pilota dall\'azienda di noleggio fino al '
                . self::dataItaliana((string) $dataFineNorm) . '.';
    }

    /**
     * Restituisce l'elenco delle prenotazioni future di un pilota con i veicoli di un'azienda di noleggio,
     * che devono essere annullate a causa di un ban o di una sospensione.
     */
    protected static function prenotazioniFutureDaStornare(int $gestoreId, int $pilotaId, ?string $finoA): array
    {
        $sql = "SELECT p.id, p.prezzo_importo
                FROM prenotazione p
                JOIN sessione s ON s.id = p.sessione_id
                JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
                WHERE v.azienda_id = :a
                  AND p.pilota_id = :p
                  AND p.stato = 'confermata'
                  AND s.fine >= NOW()";
        $args = [':a' => $gestoreId, ':p' => $pilotaId];

        if ($finoA !== null) {
            $sql .= ' AND s.inizio <= :finoA';
            $args[':finoA'] = $finoA;
        }

        $sql .= ' ORDER BY s.inizio ASC';

        return FDataBase::executeQuery($sql, $args)->fetchAllAssociative();
    }
}