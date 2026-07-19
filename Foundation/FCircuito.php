<?php

/** Repository circuiti. */
class FCircuito extends FRepository
{
    protected static function entityClass(): string
    {
        return ECircuito::class;
    }

    public static function loadById(int $id): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT c.*, g.nome_societa AS gestore_nome
             FROM circuito c
             LEFT JOIN gestore_circuiti g ON g.id = c.gestore_id
             WHERE c.id = :id',
            [':id' => $id]
        )->fetchAssociative();
        if (!$r) {
            return null;
        }
        $r['foto'] = self::loadFotoRowsByCircuitoId($id);
        return $r;
    }

    public static function loadEntityById(int $id): ?ECircuito
    {
        $circuito = FPersistentManager::find(ECircuito::class, $id);
        return $circuito instanceof ECircuito ? $circuito : null;
    }

    /**carica le foto associate a un circuito */
    private static function loadFotoRowsByCircuitoId(int $circuitoId): array
    {
        return FDataBase::executeQuery(
            'SELECT id, path_file, ordine, didascalia
             FROM circuito_foto
             WHERE circuito_id = :id
             ORDER BY ordine ASC, id ASC',
            [':id' => $circuitoId]
        )->fetchAllAssociative();
    }

    /**carica i circuiti gestiti da un gestore */
    public static function loadByGestore(int $gestoreId): array
    {
        return FDataBase::executeQuery(
            'SELECT * FROM circuito WHERE gestore_id = :g ORDER BY nome_circuito',
            [':g' => $gestoreId]
        )->fetchAllAssociative();
    }

    /** carica l'elenco di tutti i circuiti registrati */
    public static function loadElenco(): array
    {
        return FDataBase::executeQuery(
            'SELECT id, nome_circuito, localita FROM circuito ORDER BY nome_circuito'
        )->fetchAllAssociative();
    }

    /**restituisce la query per caricare i circuiti con il numero di veicoli a loro associati*/
    private static function sqlSelectWithCounts(): string
    {
        return "SELECT c.id, c.nome_circuito, c.indirizzo, c.localita, c.lunghezza_km,
                       c.tipologia_veicoli, c.numero_box,
                       (SELECT COUNT(*) FROM veicolo_noleggio v WHERE v.circuito_id = c.id) AS veicoli_noleggio,
                       (SELECT cf.path_file FROM circuito_foto cf
                        WHERE cf.circuito_id = c.id ORDER BY cf.ordine ASC, cf.id ASC LIMIT 1) AS copertina";
    }

    /**carica i circuiti con il numero di veicoli a loro associati*/
    public static function loadWithVeicoliCount(?int $limit = null): array
    {
        $sql = self::sqlSelectWithCounts() . ' FROM circuito c ORDER BY c.nome_circuito';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return FDataBase::executeQuery($sql)->fetchAllAssociative();
    }

    /**restituisce i circuiti piu popolari in base a numero prenotazioni nel mese corrente*/
    public static function loadPopolariMensile(int $limit = 4): array
    {
        $sql = self::sqlSelectWithCounts() . ",
                       (SELECT COUNT(*) FROM prenotazione p
                        JOIN sessione s ON s.id = p.sessione_id
                        WHERE s.circuito_id = c.id
                          AND p.stato = 'confermata'
                          AND p.data_inserimento >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                       ) AS prenotazioni_mese
                FROM circuito c
                ORDER BY prenotazioni_mese DESC, c.nome_circuito
                LIMIT " . (int) $limit;

        return FDataBase::executeQuery($sql)->fetchAllAssociative();
    }

    /** restituisce true se il circuito esiste ed è gestito dal gestore indicato. */
    public static function isDelGestore(int $circuitoId, int $gestoreId): bool
    {
        if ($circuitoId < 1) {
            return false;
        }

        return (int) FDataBase::executeQuery(
            'SELECT COUNT(*) FROM circuito WHERE id = :id AND gestore_id = :g',
            [':id' => $circuitoId, ':g' => $gestoreId]
        )->fetchOne() > 0;
    }

}
