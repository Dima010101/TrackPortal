<?php

/**Punto di accesso unico al foundation
 * Contiene metodi CRUD mentre per quelli piu complicati rimanda alle repository specifiche
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;


class FPersistentManager
{
    /**costanti */
    public const CAMBIO_VALUTA_SUPPORTATE = \FCambioValuta::SUPPORTATE;

    /**metodi di redirect */
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

    public static function gestoreCircuitiIsAffiliazioneApprovata(int $uid): bool
    {
        return \FGestoreCircuiti::isAffiliazioneApprovata($uid);
    }

    public static function promozioneDeleteOwned(\EPromozione $p): void
    {
        \FPromozione::deleteOwned($p);
    }

    public static function promozioneLoadByCreatore(int $accountId): array
    {
        return \FPromozione::loadByCreatorAccount($accountId);
    }

    public static function veicoloNoleggioLoadByAzienda(int $aziendaId, ?string $categoria = NULL): array
    {
        return \FVeicoloNoleggio::loadByAzienda($aziendaId, $categoria);
    }

    public static function circuitoLoadByGestore(int $gestoreId): array
    {
        return \FCircuito::loadByGestore($gestoreId);
    }

    public static function veicoloNoleggioLoadByIdAndAzienda(int $id, int $aziendaId): ?array
    {
        return \FVeicoloNoleggio::loadByIdAndAzienda($id, $aziendaId);
    }

    public static function promozioneLoadByVeicolo(int $veicoloId): array
    {
        return \FPromozione::loadByVeicolo($veicoloId);
    }

    public static function promozioneLoadByCircuitoConDettagli(int $circuitoId): array
    {
        return \FPromozione::loadByCircuitoConDettagli($circuitoId);
    }

    public static function veicoloNoleggioLoadCircuitiByAzienda(int $aziendaId): array
    {
        return \FVeicoloNoleggio::loadCircuitiByAzienda($aziendaId);
    }

    public static function circuitoLoadElenco(): array
    {
        return \FCircuito::loadElenco();
    }

    public static function veicoloNoleggioCreaDaForm(int $aziendaId, array $dati): array
    {
        return \FVeicoloNoleggio::creaDaForm($aziendaId, $dati);
    }

    public static function veicoloNoleggioLoadByCircuitoAndAzienda(int $circuitoId, int $aziendaId, ?string $categoria = NULL): array
    {
        return \FVeicoloNoleggio::loadByCircuitoAndAzienda($circuitoId, $aziendaId, $categoria);
    }

    public static function veicoloNoleggioAggiornaDaForm(int $veicoloId, int $aziendaId, array $dati): array
    {
        return \FVeicoloNoleggio::aggiornaDaForm($veicoloId, $aziendaId, $dati);
    }

    public static function veicoloNoleggioDeleteByIdAndAzienda(int $id, int $aziendaId): void
    {
        \FVeicoloNoleggio::deleteByIdAndAzienda($id, $aziendaId);
    }

    public static function veicoloNoleggioTargaInUso(string $targa, int $escludiId = 0): bool
    {
        return \FVeicoloNoleggio::targaInUso($targa, $escludiId);
    }


}