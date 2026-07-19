<?php

// repository delle carte di credito salvate dai piloti, solo quelle scelte al pagamento
class FCartaCredito extends FRepository
{
    protected static function entityClass(): string
    {
        return ECartaCredito::class;
    }

    // carte salvate del pilota, senza dati sensibili
    public static function loadByPilota(int $pilotaId): array
    {
        return FDataBase::executeQuery(
            'SELECT id, numero_masked, nome_titolare, cognome_titolare, data_scadenza
             FROM carta_credito
             WHERE pilota_id = :p
             ORDER BY id DESC',
            [':p' => $pilotaId]
        )->fetchAllAssociative();
    }

    // carta salvata solo se appartiene al pilota
    public static function findOwned(int $id, int $pilotaId): ?ECartaCredito
    {
        $carta = FPersistentManager::find(ECartaCredito::class, $id);

        return ($carta instanceof ECartaCredito && $carta->getPilotaId() === $pilotaId)
            ? $carta
            : null;
    }

    // salva la carta del pilota riusando l'eventuale riga identica già presente
    public static function salva(
        int $pilotaId,
        string $nomeTitolare,
        string $cognomeTitolare,
        string $numeroMasked,
        string $dataScadenza
    ): int {
        $esistente = FDataBase::executeQuery(
            'SELECT id FROM carta_credito
             WHERE pilota_id = :p AND numero_masked = :num AND data_scadenza = :scad
             LIMIT 1',
            [':p' => $pilotaId, ':num' => $numeroMasked, ':scad' => $dataScadenza]
        )->fetchOne();
        if ($esistente) {
            return (int) $esistente;
        }

        return (int) FPersistentManager::store(new ECartaCredito(
            $pilotaId,
            $nomeTitolare,
            $cognomeTitolare,
            $numeroMasked,
            $dataScadenza
        ));
    }
}
