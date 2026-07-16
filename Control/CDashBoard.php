<?php

/**
 * pagine iniziali per ruolo (home del pilota, dashboard di gestore/noleggio/admin).
 */
class CDashboard
{
    /** Azione base invocata dall'URL di sezione*/
    public const DEFAULT_ACTION = 'visualizzaDashboard';

    /**instrada ciascun ruolo alla propria dashboard ( GET /dashboard) */
    public function visualizzaDashboard(): void
    {
        CAuth::richiediLogin();
        $user = CAuth::utenteCorrente();

        match ((string) ($user['ruolo'] ?? '')) {
            EPilota::$ruolo          => self::pilota(),
            EGestoreCircuiti::$ruolo => self::gestore(),
            EGestoreNoleggio::$ruolo => self::noleggio(),
            EAmministratore::$ruolo  => self::admin(),
            default                  => redirect('/'),
        };
    }

    /** Home del pilota con prenotazioni attive, stato profilo e circuiti popolari. */
    private static function pilota(): void
    {
        CAuth::richiediRuolo(EPilota::$ruolo);
        $uid = (int) CAuth::utenteCorrente()['id'];

        VDashboard::home(
            FPersistentManager::prenotazioneLoadProssimeByPilota($uid, 5),
            self::profiloPilota(CAuth::utenteCorrenteDominio()),
            FPersistentManager::circuitoLoadPopolariMensile(4)
        );
    }

    /** Dashboard gestore circuiti: statistiche del mese e andamento prenotazioni */
    private static function gestore(): void
    {
        $uid      = CAuth::idUtenteConRuolo(EGestoreCircuiti::$ruolo);
        $circuiti = FPersistentManager::circuitoLoadByGestore($uid);
        $mesi     = self::finestraMesi();
        $circuitoSel = self::circuitoSelezionato($circuiti);

        VDashboard::gestore(
            FPersistentManager::sessioneCountConcluseMeseByGestore($uid),
            FPersistentManager::prenotazioneCountFutureByGestore($uid),
            FPersistentManager::promozioneCountAttiveByCreator($uid),
            FPersistentManager::prenotazioneSumGuadagnoMeseGestore($uid),
            FPersistentManager::gestoreCircuitiGetAffiliazione($uid),
            FPersistentManager::prenotazioneAndamentoMensileGestore($uid, $mesi, $circuitoSel ?: null),
            $circuiti,
            $circuitoSel,
            $mesi
        );
    }

    /** Dashboard azienda di noleggio: flotta, noleggi attivi e andamento */
    private static function noleggio(): void
    {
        $uid  = CAuth::idUtenteConRuolo(EGestoreNoleggio::$ruolo);
        $mesi = self::finestraMesi();

        VDashboard::noleggio(
            FPersistentManager::veicoloNoleggioCountByAzienda($uid),
            FPersistentManager::veicoloNoleggioCountDisponibiliByAzienda($uid),
            FPersistentManager::prenotazioneCountByAziendaAttive($uid),
            FPersistentManager::prenotazioneSumGuadagnoAzienda($uid),
            FPersistentManager::veicoloNoleggioLoadTopByAzienda($uid, 5),
            FPersistentManager::veicoloNoleggioLoadCircuitiByAzienda($uid),
            FPersistentManager::prenotazioneLoadProssimiNoleggiByAzienda($uid, 6),
            FPersistentManager::prenotazioneAndamentoMensileAzienda($uid, $mesi),
            $mesi
        );
    }

    /** Dashboard amministratore: statistiche, affiliazioni da approvare e assicurazioni. */
    private static function admin(): void
    {
        CAuth::richiediRuolo(EAmministratore::$ruolo);
        $mesi        = self::finestraMesi();
        $pendingCirc = FPersistentManager::gestoreCircuitiCountPending();
        $pendingNol  = FPersistentManager::gestoreNoleggioCountPending();
        $pendingPil  = FPersistentManager::pilotaCountPending();
        $pctComm     = (float) FPersistentManager::configurazionePiattaformaPercentualeCommissione();
        $ricavi      = FPersistentManager::prenotazioneSumRicaviConfermate();

        VDashboard::admin(
            FPersistentManager::accountCountAll(), FPersistentManager::circuitoCountAll(),
            FPersistentManager::prenotazioneCountAll(), FPersistentManager::prenotazioneCountConAssicurazione(),
            $pendingCirc + $pendingNol + $pendingPil,
            FPersistentManager::configurazionePiattaformaPrezzoAssicurazione(), (string) $pctComm,
            $ricavi, EConfigurazionePiattaforma::calcolaCommissione($ricavi, $pctComm),
            self::utentiPerRuolo(), FPersistentManager::prenotazioneLoadUltimePerFatturazione(6),
            $pendingCirc, $pendingNol, $pendingPil,
            FPersistentManager::prenotazioneAndamentoMensileAssicurazioni($mesi), $mesi
        );
    }

    
}