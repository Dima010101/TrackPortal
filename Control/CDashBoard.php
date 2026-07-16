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

    /** Finestra temporale grafici (3 / 6 / 12 mesi) */
    private static function finestraMesi(): int
    {
        $mesi = (int) get('mesi', '6');

        return in_array($mesi, [3, 6, 12], true) ? $mesi : 6;
    }

    /** selezione circuito selezionato per il grafico tra quelli del gestore (0 = tutti)
     */
    private static function circuitoSelezionato(array $circuiti): int
    {
        $sel = (int) get('circuito', '0');
        $ids = array_map(static fn (array $c): int => (int) $c['id'], $circuiti);

        return $sel > 0 && in_array($sel, $ids, true) ? $sel : 0;
    }

    /** conteggio account per ruolo per grafico a torta */
    private static function utentiPerRuolo(): array
    {
        return [
            'pilota'           => FPersistentManager::countAll(EPilota::class),
            'gestore_circuiti' => FPersistentManager::countAll(EGestoreCircuiti::class),
            'gestore_noleggio' => FPersistentManager::countAll(EGestoreNoleggio::class),
            'admin'            => FPersistentManager::countAll(EAmministratore::class),
        ];
    }

    /** dati profilo del pilota (categoria, documenti, licenza, stato di scadenza) */
    private static function profiloPilota(?EAccount $dominio): array
    {
        $profilo = [
            'categoria' => '', 'licenza' => null, 'scadenza' => null,
            'scadenza_label' => '', 'scadenza_stato' => '', 'giorni_scadenza' => null,
            'documenti_stato' => '', 'documenti_completi' => true,
        ];
        if (!$dominio instanceof EPilota) {
            return $profilo;
        }

        $profilo['categoria']          = $dominio->getCategoria();
        $profilo['documenti_stato']    = $dominio->getDocumentiStato();
        $profilo['documenti_completi'] = (string) $dominio->getCertificatoMedicoPath() !== ''
            && (string) $dominio->getLicenzaPath() !== '';
        $profilo['licenza']            = $dominio->getLicenza();
        $profilo['scadenza']           = $dominio->getScadenzaLicenza();

        return array_merge($profilo, self::statoScadenzaLicenza($dominio->getScadenzaLicenza()));
    }

    /** definizione etichetta e stato della scadenza licenza (scaduta / in scadenza a 30 giorni / ok) */
    private static function statoScadenzaLicenza(?string $scadenza): array
    {
        if (!is_string($scadenza) || $scadenza === '' || strtotime($scadenza) === false) {
            return [];
        }

        $giorni = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($scadenza))->format('%r%a');

        return [
            'scadenza_label'  => date('d/m/Y', (int) strtotime($scadenza)),
            'giorni_scadenza' => $giorni,
            'scadenza_stato'  => $giorni < 0 ? 'scaduta' : ($giorni <= 30 ? 'in_scadenza' : 'ok'),
        ];
    }
}