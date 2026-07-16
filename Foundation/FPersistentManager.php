<?php

/**Punto di accesso unico al foundation
 * Contiene metodi CRUD mentre per quelli piu complicati rimanda alle repository specifiche
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;


class FPersistentManager
{
    /**COSTANTI */
    public const CAMBIO_VALUTA_SUPPORTATE = \FCambioValuta::SUPPORTATE;
    public const CIRCUITO_FOTO_MAX_FOTO = \FCircuitoFoto::MAX_FOTO;
    public const SESSIONE_ORE_GIORNO = \FSessione::ORE_GIORNO;

    /**METODI PROPRI DEL MANAGER */

    private static ?EntityManagerInterface $em = null;

    /**Restituisce l'EntityManager di Doctrine*/
    public static function em(): EntityManagerInterface
    {
        return self::$em ??= FEntityManager::getInstance();
    }

    public static function connection(): Connection
    {
        return self::em()->getConnection();
    }

    /**wrappa in transaction usando metodo di doctrine*/
    public static function transaction(callable $callback): mixed
    {
        return self::em()->wrapInTransaction(
            static fn (): mixed => $callback(self::em())
        );
    }

    public static function find(string $entityClass, int|string $id, LockMode|int|null $lock = null): ?object
    {
        return self::em()->find($entityClass, $id, $lock);
    }

    public static function findForUpdate(string $entityClass, int|string $id): ?object
    {
        return self::find($entityClass, $id, LockMode::PESSIMISTIC_WRITE);
    }

    /**cerca rispetto ad un criterio diverso da id */
    public static function findOneBy(string $entityClass, array $criteria): ?object
    {
        return self::em()->getRepository($entityClass)->findOneBy($criteria);
    }

    public static function persist(object $entity, bool $flush = true): void
    {
        self::em()->persist($entity);
        if ($flush) {
            self::em()->flush();
        }
    }

    public static function flush(): void
    {
        self::em()->flush();
    }

    public static function remove(object $entity, bool $flush = true): void
    {
        self::em()->remove($entity);
        if ($flush) {
            self::em()->flush();
        }
    }


    /**Persiste un oggetto Entity e ne restituisce l'identificatore */
    public static function store(object $entity): mixed
    {
        if ($entity instanceof EVeicoloNoleggio) {
            return FVeicoloNoleggio::store($entity);
        }

        self::persist($entity);

        // L'identificatore generato viene letto dai metadati Doctrine
        $ids = self::em()->getClassMetadata($entity::class)->getIdentifierValues($entity);

        return count($ids) === 1 ? reset($ids) : ($ids === [] ? null : $ids);
    }

    /** converte entity in un array associativo per il salvataggio nel db */
    public static function entityToRow(object $entity): array
    {
        $meta = self::em()->getClassMetadata($entity::class);
        $row  = [];

        foreach ($meta->getFieldNames() as $field) {
            $row[$meta->getColumnName($field)] = $meta->getFieldValue($entity, $field);
        }

        // Gerarchie ORM (es. EAccount, JOINED): la colonna discriminatore
        // (es. `ruolo`) appartiene alla riga ma non ai field mappati; il suo
        // valore viene ricavato dai metadati Doctrine
        if ($meta->discriminatorColumn !== null && $meta->discriminatorValue !== null) {
            $row[$meta->discriminatorColumn['name']] = $meta->discriminatorValue;
        }

        return $row;
    }

    /** conteggio totale di record di una entity */
    public static function countAll(string $entityClass): int
    {
        $meta = self::em()->getClassMetadata($entityClass);
        $id   = $meta->getSingleIdentifierFieldName();

        return (int) self::em()->createQueryBuilder()
            ->select("COUNT(e.{$id})")
            ->from($entityClass, 'e')
            ->getQuery()
            ->getSingleScalarResult();
    }



    /**METODI DI REDIRECT ALLE SINGOLE REPOSITORY */
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

    public static function promozioneFindOwned(int $id, int $accountId): ?\EPromozione
    {
        return \FPromozione::findOwned($id, $accountId);
    }

    public static function cambioValutaTassi(): array
    {
        return \FCambioValuta::tassi();
    }

    public static function pilotaLoadEntityByUtente(int $uid): ?\EPilota
    {
        return \FPilota::loadEntityByUtente($uid);
    }

    public static function sessioneLoadById(int $id): ?\ESessione
    {
        return \FSessione::loadById($id);
    }

    public static function sessioneCountPrenotazioniAttive(int $circuitoId, string $inizio, string $fine): int
    {
        return \FSessione::countPrenotazioniAttive($circuitoId, $inizio, $fine);
    }

    public static function veicoloNoleggioLoadById(int $id): ?array
    {
        return \FVeicoloNoleggio::loadById($id);
    }

    public static function sanzionePilotaNoleggioPilotaBloccatoSuAzienda(int $pilotaId, int $aziendaId): ?array
    {
        return \FSanzionePilotaNoleggio::pilotaBloccatoSuAzienda($pilotaId, $aziendaId);
    }

    public static function veicoloNoleggioPrenotatoInIntervallo(int $veicoloId, string $inizio, string $fine): bool
    {
        return \FVeicoloNoleggio::prenotatoInIntervallo($veicoloId, $inizio, $fine);
    }

    public static function sanzionePilotaPilotaBloccatoSuCircuito(int $pilotaId, int $circuitoId): ?array
    {
        return \FSanzionePilota::pilotaBloccatoSuCircuito($pilotaId, $circuitoId);
    }

    public static function sessionePilotaHaPrenotazioneAttiva(int $pilotaId, int $circuitoId, string $inizio, string $fine): bool
    {
        return \FSessione::pilotaHaPrenotazioneAttiva($pilotaId, $circuitoId, $inizio, $fine);
    }

    public static function cartaCreditoLoadByPilota(int $pilotaId): array
    {
        return \FCartaCredito::loadByPilota($pilotaId);
    }

    public static function cartaCreditoFindOwned(int $id, int $pilotaId): ?\ECartaCredito
    {
        return \FCartaCredito::findOwned($id, $pilotaId);
    }

    public static function prenotazioneLoadDettaglioByCodice(string $codice, int $pilotaId): ?array
    {
        return \FPrenotazione::loadDettaglioByCodice($codice, $pilotaId);
    }

    public static function fatturazioneEmettiPerPrenotazione(int $prenId): void
    {
        \FFatturazione::emettiPerPrenotazione($prenId);
    }

    public static function notifichePrenotazioneCompletata(int $prenId, int $pilotaId): void
    {
        \FNotifiche::prenotazioneCompletata($prenId, $pilotaId);
    }

    public static function prenotazioneAssegnaBox(int $circuitoId, string $inizio, string $fine, int $numeroBoxCircuito, int $postiPerBox): int
    {
        return \FPrenotazione::assegnaBox($circuitoId, $inizio, $fine, $numeroBoxCircuito, $postiPerBox);
    }

    public static function cartaCreditoSalva(int $pilotaId, string $nomeTitolare, string $cognomeTitolare, string $numeroMasked, string $dataScadenza): int
    {
        return \FCartaCredito::salva($pilotaId, $nomeTitolare, $cognomeTitolare, $numeroMasked, $dataScadenza);
    }

    public static function prenotazioneCountStoricheByPilotaCircuito(int $pilotaId, int $circuitoId): int
    {
        return \FPrenotazione::countStoricheByPilotaCircuito($pilotaId, $circuitoId);
    }

    public static function promozioneLoadAttivePerPrenotazione(int $circuitoId, ?int $veicoloId = NULL): array
    {
        return \FPromozione::loadAttivePerPrenotazione($circuitoId, $veicoloId);
    }

    public static function gestoreCircuitiGetAffiliazione(int $uid): string
    {
        return \FGestoreCircuiti::getAffiliazione($uid);
    }

    public static function circuitoIsDelGestore(int $circuitoId, int $gestoreId): bool
    {
        return \FCircuito::isDelGestore($circuitoId, $gestoreId);
    }

    public static function circuitoLoadEntityById(int $id): ?\ECircuito
    {
        return \FCircuito::loadEntityById($id);
    }

    public static function circuitoFotoNormalizzaFiles(array $files): array
    {
        return \FCircuitoFoto::normalizzaFiles($files);
    }

    public static function circuitoFotoFileVuoto(array $file): bool
    {
        return \FCircuitoFoto::fileVuoto($file);
    }

    public static function circuitoFotoValidaUpload(array $file): string
    {
        return \FCircuitoFoto::validaUpload($file);
    }

    public static function circuitoFotoSalva(int $circuitoId, array $file): string
    {
        return \FCircuitoFoto::salva($circuitoId, $file);
    }

    public static function circuitoFotoElimina(?string $webPath): void
    {
        \FCircuitoFoto::elimina($webPath);
    }

    public static function notifichePromozionePiloti(array $promo): void
    {
        \FNotifiche::promozionePiloti($promo);
    }

    public static function promozioneStore(\EPromozione $p): int
    {
        return \FPromozione::store($p);
    }

    public static function sessioneLoadEntityById(int $id): ?\ESessione
    {
        return \FSessione::loadEntityById($id);
    }

    public static function sessioneLoadByCircuitoSlot(int $circuitoId, string $data, string $ora): ?array
    {
        return \FSessione::loadByCircuitoSlot($circuitoId, $data, $ora);
    }

    public static function prenotazioneLoadBySessioneCircuito(int $circuitoId, string $inizio, string $fine): array
    {
        return \FPrenotazione::loadBySessioneCircuito($circuitoId, $inizio, $fine);
    }

    public static function sessioneLoadByIdForGestore(int $id, int $gestoreId): ?array
    {
        return \FSessione::loadByIdForGestore($id, $gestoreId);
    }

    public static function prenotazioneLoadConfermateByIntervalloCircuito(int $circuitoId, string $inizio, string $fine): array
    {
        return \FPrenotazione::loadConfermateByIntervalloCircuito($circuitoId, $inizio, $fine);
    }

    public static function sessioneAnnulla(int $sessioneId, int $gestoreId, string $causa): int
    {
        return \FSessione::annulla($sessioneId, $gestoreId, $causa);
    }

    public static function sessioneEsisteConflitto(int $circuitoId, string $inizio, string $fine, ?int $escludiSessioneId = NULL): bool
    {
        return \FSessione::esisteConflitto($circuitoId, $inizio, $fine, $escludiSessioneId);
    }

    public static function sessioneEsisteConflittoPrenotazione(int $circuitoId, string $inizio, string $fine): bool
    {
        return \FSessione::esisteConflittoPrenotazione($circuitoId, $inizio, $fine);
    }

    public static function sessioneEsisteConflittoPrenotazioneEscludendoIntervallo(int $circuitoId, string $inizio, string $fine, string $escludiInizio, string $escludiFine): bool
    {
        return \FSessione::esisteConflittoPrenotazioneEscludendoIntervallo($circuitoId, $inizio, $fine, $escludiInizio, $escludiFine);
    }

    public static function cambioValutaInEuro(float $importo, string $valuta): float
    {
        return \FCambioValuta::inEuro($importo, $valuta);
    }

    public static function sessioneStore(\ESessione $s): int
    {
        return \FSessione::store($s);
    }

    public static function configurazionePiattaformaPercentualeCommissione(): float
    {
        return \FConfigurazionePiattaforma::percentualeCommissione();
    }

    public static function configurazionePiattaformaAliquotaIva(): float
    {
        return \FConfigurazionePiattaforma::aliquotaIva();
    }

    public static function prenotazioneCountConAssicurazione(): int
    {
        return \FPrenotazione::countConAssicurazione();
    }

    public static function configurazionePiattaformaImpostaEconomici(float $prezzoAssicurazione, float $percentualeCommissione, float $aliquotaIva): bool
    {
        return \FConfigurazionePiattaforma::impostaEconomici($prezzoAssicurazione, $percentualeCommissione, $aliquotaIva);
    }

    public static function configurazionePiattaformaStoricoUltimi(int $limit = 10): array
    {
        return \FConfigurazionePiattaforma::storicoUltimi($limit);
    }

    public static function prenotazioneLoadProssimeByPilota(int $pilotaId, int $limit = 5): array
    {
        return \FPrenotazione::loadProssimeByPilota($pilotaId, $limit);
    }

    public static function circuitoLoadPopolariMensile(int $limit = 4): array
    {
        return \FCircuito::loadPopolariMensile($limit);
    }

    public static function sessioneCountConcluseMeseByGestore(int $gestoreId): int
    {
        return \FSessione::countConcluseMeseByGestore($gestoreId);
    }

    public static function prenotazioneCountFutureByGestore(int $gestoreId): int
    {
        return \FPrenotazione::countFutureByGestore($gestoreId);
    }

    public static function promozioneCountAttiveByCreator(int $accountId): int
    {
        return \FPromozione::countAttiveByCreator($accountId);
    }

    public static function prenotazioneSumGuadagnoMeseGestore(int $gestoreId): float
    {
        return \FPrenotazione::sumGuadagnoMeseGestore($gestoreId);
    }

    public static function prenotazioneAndamentoMensileGestore(int $gestoreId, int $mesi = 6, ?int $circuitoId = NULL): array
    {
        return \FPrenotazione::andamentoMensileGestore($gestoreId, $mesi, $circuitoId);
    }

    public static function veicoloNoleggioCountByAzienda(int $aziendaId): int
    {
        return \FVeicoloNoleggio::countByAzienda($aziendaId);
    }

    public static function veicoloNoleggioCountDisponibiliByAzienda(int $aziendaId): int
    {
        return \FVeicoloNoleggio::countDisponibiliByAzienda($aziendaId);
    }

    public static function prenotazioneCountByAziendaAttive(int $aziendaId): int
    {
        return \FPrenotazione::countByAziendaAttive($aziendaId);
    }

    public static function prenotazioneSumGuadagnoAzienda(int $aziendaId): float
    {
        return \FPrenotazione::sumGuadagnoAzienda($aziendaId);
    }

    public static function veicoloNoleggioLoadTopByAzienda(int $aziendaId, int $limit = 5): array
    {
        return \FVeicoloNoleggio::loadTopByAzienda($aziendaId, $limit);
    }

    public static function prenotazioneLoadProssimiNoleggiByAzienda(int $aziendaId, int $limit = 6): array
    {
        return \FPrenotazione::loadProssimiNoleggiByAzienda($aziendaId, $limit);
    }

    public static function prenotazioneAndamentoMensileAzienda(int $aziendaId, int $mesi = 6): array
    {
        return \FPrenotazione::andamentoMensileAzienda($aziendaId, $mesi);
    }

    public static function gestoreCircuitiCountPending(): int
    {
        return \FGestoreCircuiti::countPending();
    }

    public static function gestoreNoleggioCountPending(): int
    {
        return \FGestoreNoleggio::countPending();
    }

    public static function pilotaCountPending(): int
    {
        return \FPilota::countPending();
    }

    public static function prenotazioneSumRicaviConfermate(): float
    {
        return \FPrenotazione::sumRicaviConfermate();
    }

    public static function accountCountAll(): int
    {
        return \FAccount::countAll();
    }

    public static function prenotazioneCountAll(): int
    {
        return \FPrenotazione::countAll();
    }

    public static function prenotazioneLoadUltimePerFatturazione(int $limit = 20): array
    {
        return \FPrenotazione::loadUltimePerFatturazione($limit);
    }

    public static function prenotazioneAndamentoMensileAssicurazioni(int $mesi = 6): array
    {
        return \FPrenotazione::andamentoMensileAssicurazioni($mesi);
    }

}