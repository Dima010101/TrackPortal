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

    public static function prenotazioneLoadByPilotaFiltro(int $pilotaId, string $filtro, string $q = ''): array
    {
        return \FPrenotazione::loadByPilotaFiltro($pilotaId, $filtro, $q);
    }

    public static function prenotazioneLoadByGestoreForFatturazione(int $gestoreId, string $q = ''): array
    {
        return \FPrenotazione::loadByGestoreForFatturazione($gestoreId, $q);
    }

    public static function sanzionePilotaPilotiSanzionabili(int $gestoreId): array
    {
        return \FSanzionePilota::pilotiSanzionabili($gestoreId);
    }

    public static function prenotazioneLoadByAziendaForFatturazione(int $aziendaId, string $q = ''): array
    {
        return \FPrenotazione::loadByAziendaForFatturazione($aziendaId, $q);
    }

    public static function sanzionePilotaNoleggioPilotiSanzionabili(int $gestoreId): array
    {
        return \FSanzionePilotaNoleggio::pilotiSanzionabili($gestoreId);
    }

    public static function documentoFiscaleFatturePilotaPerPrenotazioni(array $prenIds, int $pilotaId): array
    {
        return \FDocumentoFiscale::fatturePilotaPerPrenotazioni($prenIds, $pilotaId);
    }

    public static function configurazionePiattaformaPrezzoAssicurazione(): float
    {
        return \FConfigurazionePiattaforma::prezzoAssicurazione();
    }

    public static function prenotazioneAggiornaPrenotazione(int $id, int $pilotaId, array $dati): array
    {
        return \FPrenotazione::aggiornaPrenotazione($id, $pilotaId, $dati);
    }

    public static function fatturaPdfInvia(array $pren, string $vista): never
    {
        \FFatturaPdf::invia($pren, $vista);
    }

    public static function documentoFiscaleLoadById(int $id): ?array
    {
        return \FDocumentoFiscale::loadById($id);
    }

    public static function documentoFiscaleUtenteAutorizzato(array $doc, string $ruolo, int $id): bool
    {
        return \FDocumentoFiscale::utenteAutorizzato($doc, $ruolo, $id);
    }

    public static function fatturaPdfInviaDocumento(array $doc, array $righe): never
    {
        \FFatturaPdf::inviaDocumento($doc, $righe);
    }

    public static function documentoFiscaleLoadRighe(int $documentoId): array
    {
        return \FDocumentoFiscale::loadRighe($documentoId);
    }

    public static function prenotazioneLoadDettaglio(int $id, int $pilotaId): ?array
    {
        return \FPrenotazione::loadDettaglio($id, $pilotaId);
    }

    public static function prenotazioneCancella(int $id, int $pilotaId, string $causa, float $rimborso): void
    {
        \FPrenotazione::cancella($id, $pilotaId, $causa, $rimborso);
    }

    public static function prenotazioneLoadDettaglioForGestore(int $id, int $gestoreId): ?array
    {
        return \FPrenotazione::loadDettaglioForGestore($id, $gestoreId);
    }

    public static function prenotazioneLoadDettaglioForAzienda(int $id, int $aziendaId): ?array
    {
        return \FPrenotazione::loadDettaglioForAzienda($id, $aziendaId);
    }

}