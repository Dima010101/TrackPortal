<?php

/** Repository promozioni. */
class FPromozione extends FRepository
{
    protected static function entityClass(): string
    {
        return EPromozione::class;
    }

    public static function store(EPromozione $p): int
    {
        return self::persistAndId($p);
    }

    /**
     *  Rimuove una promozione dal database.
     */
    public static function deleteOwned(EPromozione $p): void
    {
        FPersistentManager::remove($p);
    }

    public static function loadByCircuitoConDettagli(int $circuitoId): array
    {
        $sql = "SELECT p.*, c.nome_circuito
                     , CONCAT(v.marca, ' ', v.modello, ' (', v.targa, ')') AS nome_veicolo
                FROM promozione p
                LEFT JOIN circuito c ON c.id = p.circuito_id
                LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
                WHERE p.circuito_id = :id
                ORDER BY p.data_inizio DESC, p.id DESC";

        return FDataBase::executeQuery($sql, [':id' => $circuitoId])->fetchAllAssociative();
    }

    public static function loadByVeicolo(int $veicoloId): array
    {
        $sql = "SELECT p.*, c.nome_circuito
                     , CONCAT(v.marca, ' ', v.modello, ' (', v.targa, ')') AS nome_veicolo
                FROM promozione p
                LEFT JOIN circuito c ON c.id = p.circuito_id
                LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
                WHERE p.veicolo_noleggio_id = :id
                ORDER BY p.data_inizio DESC, p.id DESC";

        return FDataBase::executeQuery($sql, [':id' => $veicoloId])->fetchAllAssociative();
    }

    /** Numero di promozioni attive create da un account (gestore/azienda). */
    public static function countAttiveByCreator(int $accountId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM promozione
             WHERE creator_account_id = :id AND stato_promozione = 'attiva'",
            [':id' => $accountId]
        )->fetchOne();
    }

    private static function loadByProponente(string $field, int $id): array
    {
        $sql = "SELECT p.*, c.nome_circuito
                        , CONCAT(v.marca, ' ', v.modello, ' (', v.targa, ')') AS nome_veicolo
                FROM promozione p
                LEFT JOIN circuito c ON c.id = p.circuito_id
                LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
                WHERE p.{$field} = :id
                ORDER BY p.data_inizio DESC, p.id DESC";

        return FDataBase::executeQuery($sql, [':id' => $id])->fetchAllAssociative();
    }

    public static function loadByCreatorAccount(int $accountId): array
    {
        return self::loadByProponente('creator_account_id', $accountId);
    }

    /**
     * Restituisce le promozioni attive per un circuito o un veicolo, ordinate per data inizio decrescente.
     * Se il veicoloId è nullo, restituisce le promozioni attive per il circuito e quelle generiche (senza circuito né veicolo).
     * Se il veicoloId è specificato, restituisce le promozioni attive per il veicolo, quelle per il circuito e quelle generiche.
     * Le promozioni attive sono quelle con stato_promozione = 'attiva'.
     */
    public static function loadAttivePerPrenotazione(int $circuitoId, ?int $veicoloId = null): array
    {
        return FPersistentManager::em()->createQueryBuilder()
            ->select('p')
            ->from(EPromozione::class, 'p')
            ->where("p.statoPromozione = 'attiva'")
            ->andWhere('p.circuitoId = :c OR p.veicoloNoleggioId = :v
                        OR (p.circuitoId IS NULL AND p.veicoloNoleggioId IS NULL)')
            ->orderBy('p.dataInizio', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setParameter('c', $circuitoId)
            ->setParameter('v', $veicoloId ?? 0)
            ->getQuery()
            ->getResult();
    }

    public static function findOwned(int $id, int $accountId): ?EPromozione
    {
        $promo = FPersistentManager::find(EPromozione::class, $id);
        if (!$promo instanceof EPromozione || (int)$promo->getCreatorAccountId() !== $accountId) {
            return null;
        }

        return $promo;
    }
}
