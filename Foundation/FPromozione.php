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
    * Elimina la promo. Le prenotazioni collegate restano valide: 
    * 'promozione_id' si azzera (SET NULL), ma lo sconto applicato 
    * rimane salvato in 'sconto_importo'.
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