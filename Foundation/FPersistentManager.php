<?php

/**Punto di accesso unico al foundation
 * Contiene metodi CRUD mentre per quelli piu complicati rimanda alle repository specifiche
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;


class FPersistentManager
{
    public static function circuitoLoadWithVeicoliCount(?int $limit = NULL): array
        {
            return \FCircuito::loadWithVeicoliCount($limit);
        }

    public static function veicoloNoleggioLoadDisponibiliByCircuito(int $circuitoId, ?string $inizio = NULL, ?string $fine = NULL): array
    {
        return \FVeicoloNoleggio::loadDisponibiliByCircuito($circuitoId, $inizio, $fine);
    }

    public static function veicoloNoleggioLoadTopByCircuito(int $circuitoId, int $limit = 3): array
    {
        return \FVeicoloNoleggio::loadTopByCircuito($circuitoId, $limit);
    }

    public static function sessioneCalendarioSettimanale(int $circuitoId, int $settimana = 0, ?int $pilotaId = NULL): array
    {
        return \FSessione::calendarioSettimanale($circuitoId, $settimana, $pilotaId);
    }

    public static function circuitoLoadById(int $id): ?array
    {
        return \FCircuito::loadById($id);
    }
}