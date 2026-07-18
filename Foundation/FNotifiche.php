<?php

/**
 * Notifiche email di dominio.
 *
 * Ogni metodo prepara le variabili, renderizza email/messaggio.tpl e delega a
 * FMailer. Tutto best-effort: gli errori finiscono nel log e una
 * notifica fallita non blocca mai l'operazione che l'ha generata.
 */
class FNotifiche
{
    private const TPL = 'email/messaggio.tpl';

    // -- Autenticazione -----------------------------------------------------

    /** Email di conferma registrazione con link di verifica. */
    public static function confermaRegistrazione(array $account, string $token): bool
    {
        if (self::disabilitato()) {
            return false;
        }

        $link = absolute_url('/auth/conferma-email/' . rawurlencode($token));

        return self::messaggio(
            (string) ($account['email'] ?? ''),
            self::nomeCompleto($account),
            'Conferma il tuo indirizzo email — ' . self::appName(),
            [
                'titolo'    => 'Conferma la tua registrazione',
                'saluto'    => 'Ciao ' . self::nome($account) . ',',
                'paragrafi' => [
                    'grazie per esserti registrato su ' . self::appName() . '.',
                    'Per completare la registrazione e poter accedere, conferma il tuo '
                        . 'indirizzo email cliccando sul pulsante qui sotto.',
                ],
                'bottone'   => ['label' => 'Conferma email', 'url' => $link],
                'nota'      => 'Se non hai creato tu questo account, ignora questa email.',
            ]
        );
    }

    /** Email con il codice OTP per l'autenticazione a due fattori. */
    public static function codice2FA(array $account, string $code): bool
    {
        if (self::disabilitato()) {
            return false;
        }

        return self::messaggio(
            (string) ($account['email'] ?? ''),
            self::nomeCompleto($account),
            'Codice di accesso ' . $code . ' — ' . self::appName(),
            [
                'titolo'    => 'Codice di verifica',
                'saluto'    => 'Ciao ' . self::nome($account) . ',',
                'paragrafi' => [
                    'per completare l\'accesso al tuo account inserisci il seguente codice '
                        . 'di verifica. Il codice scade tra 10 minuti.',
                ],
                'codice'    => $code,
                'nota'      => 'Se non stai effettuando l\'accesso, ti consigliamo di '
                    . 'cambiare la password al più presto.',
            ]
        );
    }

    // -- Documenti pilota ---------------------------------------------------

    /** Conferma al pilota dell'approvazione dei documenti. */
    public static function documentiPilotaApprovati(array $pilota): bool
    {
        if (self::disabilitato()) {
            return false;
        }

        return self::messaggio(
            (string) ($pilota['email'] ?? ''),
            self::nomeCompleto($pilota),
            'Documenti approvati — ' . self::appName(),
            [
                'titolo'    => 'Documenti approvati',
                'saluto'    => 'Ciao ' . self::nome($pilota) . ',',
                'paragrafi' => [
                    'i tuoi documenti sono stati verificati e approvati '
                        . 'dall\'amministrazione.',
                    'Da ora puoi prenotare le sessioni in pista.',
                ],
                'bottone'   => ['label' => 'Esplora i circuiti', 'url' => absolute_url('/dashboard')],
            ]
        );
    }

    /** Avviso al pilota del rifiuto dei documenti. */
    public static function documentiPilotaRespinti(array $pilota): bool
    {
        if (self::disabilitato()) {
            return false;
        }

        return self::messaggio(
            (string) ($pilota['email'] ?? ''),
            self::nomeCompleto($pilota),
            'Documenti non approvati — ' . self::appName(),
            [
                'titolo'    => 'Documenti non approvati',
                'saluto'    => 'Ciao ' . self::nome($pilota) . ',',
                'paragrafi' => [
                    'i documenti che hai caricato non sono stati approvati '
                        . 'dall\'amministrazione.',
                    'Per poter prenotare le sessioni, carica nuovamente i documenti '
                        . '(certificato medico e patente/licenza) dal tuo account.',
                ],
                'bottone'   => ['label' => 'Aggiorna i documenti', 'url' => absolute_url('/account')],
            ]
        );
    }

    // -- Notifiche agli amministratori --------------------------------------

    /** Notifica agli admin una nuova richiesta di affiliazione (gestore/azienda). */
    public static function notificaAdminAffiliazione(array $richiedente, string $tipoLabel): void
    {
        if (self::disabilitato()) {
            return;
        }

        $dettagli = [
            'Tipo'  => $tipoLabel,
            'Nome'  => self::nomeCompleto($richiedente),
            'Email' => (string) ($richiedente['email'] ?? ''),
        ];
        if (!empty($richiedente['nome_societa'])) {
            $dettagli['Società'] = (string) $richiedente['nome_societa'];
        }
        if (!empty($richiedente['partita_iva'])) {
            $dettagli['Partita IVA'] = (string) $richiedente['partita_iva'];
        }

        self::aGliAdmin(
            'Nuova richiesta di affiliazione — ' . self::appName(),
            [
                'titolo'    => 'Nuova richiesta di affiliazione',
                'paragrafi' => [
                    'È stata registrata una nuova richiesta di affiliazione in attesa di '
                        . 'approvazione.',
                ],
                'dettagli'  => $dettagli,
                'bottone'   => ['label' => 'Vai a Convalida', 'url' => absolute_url('/affiliazioni')],
            ]
        );
    }

    /**
     * Avvisa gli admin quando un pilota ha entrambi i documenti caricati e in
     * attesa di convalida (alla registrazione o in un secondo momento).
     */
    public static function notificaAdminDocumentiPilota(int $pilotaId): void
    {
        if (self::disabilitato()) {
            return;
        }

        $pilota = FPilota::loadByUtente($pilotaId);
        if ($pilota === null || !FAffiliazione::pilotaDocumentiCompleti($pilota)) {
            return;
        }
        if ((string) ($pilota['documenti_stato'] ?? '') !== EPilota::DOC_IN_ATTESA) {
            return;
        }

        self::aGliAdmin(
            'Documenti pilota da convalidare — ' . self::appName(),
            [
                'titolo'    => 'Documenti pilota da convalidare',
                'paragrafi' => [
                    'Un pilota ha caricato i documenti richiesti: sono in attesa di '
                        . 'convalida da parte dell\'amministrazione.',
                ],
                'dettagli'  => [
                    'Pilota'    => self::nomeCompleto($pilota),
                    'Email'     => (string) ($pilota['email'] ?? ''),
                    'Categoria' => tp_ucfirst((string) ($pilota['categoria'] ?? '')),
                ],
                'bottone'   => ['label' => 'Vai a Convalida', 'url' => absolute_url('/affiliazioni')],
            ]
        );
    }

    // -- Prenotazione: pilota + gestore circuito + azienda noleggio ---------

    /**
     * Alla conferma di una prenotazione invia la ricevuta PDF a pilota, gestore
     * del circuito e (solo se c'è un noleggio) azienda. Ogni invio è isolato:
     * se uno fallisce gli altri partono comunque.
     */
    public static function prenotazioneCompletata(int $prenId, int $pilotaId): void
    {
        if (self::disabilitato()) {
            return;
        }

        $detPilota = FPrenotazione::loadDettaglio($prenId, $pilotaId);
        if ($detPilota === null) {
            return;
        }

        $codice  = (string) ($detPilota['codice_identificativo'] ?? '');
        $periodo = self::periodo($detPilota);
        $importo = self::importo($detPilota);
        $nomeRicevuta = 'ricevuta-' . (preg_replace('/[^A-Za-z0-9\-_]/', '', $codice) ?: 'documento') . '.pdf';

        // 1) Pilota
        try {
            $pilotaAccount = FAccount::loadById($pilotaId) ?? [];
            $pdf = FFatturaPdf::genera($detPilota, 'pilota');
            self::messaggio(
                (string) ($pilotaAccount['email'] ?? ''),
                self::nomeCompleto($pilotaAccount),
                'Prenotazione confermata ' . $codice . ' — ' . self::appName(),
                [
                    'titolo'    => 'Prenotazione confermata',
                    'saluto'    => 'Ciao ' . self::nome($pilotaAccount) . ',',
                    'paragrafi' => [
                        'la tua prenotazione è stata confermata. Trovi la ricevuta in allegato.',
                    ],
                    'dettagli'  => [
                        'Codice'   => $codice,
                        'Circuito' => (string) ($detPilota['nome_circuito'] ?? ''),
                        'Periodo'  => $periodo,
                        'Importo'  => $importo,
                    ],
                ],
                [['data' => $pdf, 'name' => $nomeRicevuta, 'mime' => 'application/pdf']]
            );
        } catch (Throwable $e) {
            error_log('FNotifiche prenotazione (pilota): ' . $e->getMessage());
        }

        // 2) Gestore del circuito
        try {
            $circuitoId = (int) ($detPilota['circuito_id'] ?? 0);
            $circuito   = $circuitoId > 0 ? FCircuito::loadById($circuitoId) : null;
            $gestoreId  = (int) ($circuito['gestore_id'] ?? 0);
            if ($gestoreId > 0) {
                $detGestore = FPrenotazione::loadDettaglioForGestore($prenId, $gestoreId);
                $gestoreAcc = FAccount::loadById($gestoreId) ?? [];
                if ($detGestore !== null && ($gestoreAcc['email'] ?? '') !== '') {
                    $pdfG = FFatturaPdf::genera($detGestore, 'gestore');
                    self::messaggio(
                        (string) $gestoreAcc['email'],
                        self::nomeCompleto($gestoreAcc),
                        'Nuova prenotazione ' . $codice . ' — ' . self::appName(),
                        [
                            'titolo'    => 'Nuova prenotazione sul tuo circuito',
                            'paragrafi' => [
                                'È stata registrata una nuova prenotazione su un tuo circuito. '
                                    . 'In allegato la ricevuta.',
                            ],
                            'dettagli'  => [
                                'Codice'   => $codice,
                                'Circuito' => (string) ($detGestore['nome_circuito'] ?? ''),
                                'Pilota'   => trim((string) ($detGestore['pilota_nome'] ?? '')
                                    . ' ' . (string) ($detGestore['pilota_cognome'] ?? '')),
                                'Periodo'  => $periodo,
                                'Importo'  => $importo,
                            ],
                        ],
                        [['data' => $pdfG, 'name' => $nomeRicevuta, 'mime' => 'application/pdf']]
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('FNotifiche prenotazione (gestore): ' . $e->getMessage());
        }

        // 3) Azienda di noleggio (solo se veicolo noleggiato)
        if (empty($detPilota['veicolo_noleggio_id'])) {
            return;
        }
        try {
            $veicolo   = FVeicoloNoleggio::loadById((int) $detPilota['veicolo_noleggio_id']);
            $aziendaId = (int) ($veicolo['azienda_id'] ?? 0);
            if ($aziendaId > 0) {
                $detAzienda = FPrenotazione::loadDettaglioForAzienda($prenId, $aziendaId);
                $aziendaAcc = FAccount::loadById($aziendaId) ?? [];
                if ($detAzienda !== null && ($aziendaAcc['email'] ?? '') !== '') {
                    $pdfA   = FFatturaPdf::genera($detAzienda, 'noleggio');
                    $veicoloDescr = trim((string) ($detAzienda['v_marca'] ?? '')
                        . ' ' . (string) ($detAzienda['v_modello'] ?? '')
                        . ' (' . (string) ($detAzienda['v_targa'] ?? '') . ')');
                    self::messaggio(
                        (string) $aziendaAcc['email'],
                        self::nomeCompleto($aziendaAcc),
                        'Veicolo prenotato ' . $codice . ' — ' . self::appName(),
                        [
                            'titolo'    => 'Un tuo veicolo è stato prenotato',
                            'paragrafi' => [
                                'Uno dei tuoi veicoli è stato prenotato per una sessione in pista. '
                                    . 'In allegato la ricevuta.',
                            ],
                            'dettagli'  => [
                                'Codice'  => $codice,
                                'Veicolo' => $veicoloDescr,
                                'Periodo' => $periodo,
                                'Importo' => $importo,
                            ],
                        ],
                        [['data' => $pdfA, 'name' => $nomeRicevuta, 'mime' => 'application/pdf']]
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('FNotifiche prenotazione (noleggio): ' . $e->getMessage());
        }
    }

    // -- Promozioni ---------------------------------------------------------

    /** Avvisa i piloti idonei di una nuova promozione ($promo: riga promozione). */
    public static function promozionePiloti(array $promo): void
    {
        if (self::disabilitato()) {
            return;
        }

        $piloti = FPilota::loadIdoneiPerPromozione($promo);
        if ($piloti === []) {
            return;
        }

        $titoloPromo = (string) ($promo['titolo'] ?? 'Promozione');
        $descr       = trim((string) ($promo['descrizione'] ?? ''));

        $dettagli = ['Promozione' => $titoloPromo, 'Sconto' => self::scontoLabel($promo)];
        $validita = self::validitaLabel($promo);
        if ($validita !== '') {
            $dettagli['Validità'] = $validita;
        }
        if (!empty($promo['nome_circuito'])) {
            $dettagli['Circuito'] = (string) $promo['nome_circuito'];
        }
        if (!empty($promo['nome_veicolo'])) {
            $dettagli['Veicolo'] = (string) $promo['nome_veicolo'];
        }

        $paragrafi = ['è disponibile una nuova promozione di cui puoi approfittare su '
            . self::appName() . '.'];
        if ($descr !== '') {
            $paragrafi[] = $descr;
        }
        $paragrafi[] = 'Lo sconto viene applicato automaticamente quando prenoti una sessione idonea.';

        foreach ($piloti as $pilota) {
            self::messaggio(
                (string) ($pilota['email'] ?? ''),
                self::nomeCompleto($pilota),
                'Nuova promozione per te — ' . self::appName(),
                [
                    'titolo'    => 'Nuova promozione: ' . $titoloPromo,
                    'saluto'    => 'Ciao ' . self::nome($pilota) . ',',
                    'paragrafi' => $paragrafi,
                    'dettagli'  => $dettagli,
                    'bottone'   => ['label' => 'Prenota ora', 'url' => absolute_url('/dashboard')],
                ]
            );
        }
    }

    // -- Helper interni -----------------------------------------------------

    private static function messaggio(
        string $toEmail,
        string $toName,
        string $subject,
        array $vars,
        array $allegati = []
    ): bool {
        try {
            $html = FMailer::render(self::TPL, $vars);
        } catch (Throwable $e) {
            error_log('FNotifiche render: ' . $e->getMessage());
            return false;
        }

        return FMailer::invia($toEmail, $toName, $subject, $html, $allegati);
    }

    private static function aGliAdmin(string $subject, array $vars): void
    {
        foreach (FAccount::loadAdminEmails() as $admin) {
            self::messaggio(
                (string) ($admin['email'] ?? ''),
                self::nomeCompleto($admin),
                $subject,
                $vars
            );
        }
    }

    private static function nome(array $a): string
    {
        return trim((string) ($a['nome'] ?? '')) ?: 'utente';
    }

    private static function nomeCompleto(array $a): string
    {
        return trim((string) ($a['nome'] ?? '') . ' ' . (string) ($a['cognome'] ?? ''));
    }

    private static function periodo(array $det): string
    {
        $inizio = date_pretty((string) ($det['inizio_sessione'] ?? ''));
        $fine   = date_pretty((string) ($det['fine_sessione'] ?? ''));
        if ($inizio === '' && $fine === '') {
            return '';
        }

        return trim($inizio . ' – ' . $fine, ' –');
    }

    private static function importo(array $det): string
    {
        return money(
            (float) ($det['prezzo_importo'] ?? 0),
            (string) ($det['prezzo_valuta'] ?? 'EUR')
        );
    }

    private static function scontoLabel(array $promo): string
    {
        $valore = (float) ($promo['valore'] ?? 0);
        if ((string) ($promo['tipo_sconto'] ?? '') === 'importo') {
            return money($valore, 'EUR');
        }

        return rtrim(rtrim(number_format($valore, 2, ',', '.'), '0'), ',') . '%';
    }

    private static function validitaLabel(array $promo): string
    {
        $inizio = trim((string) ($promo['data_inizio'] ?? ''));
        $fine   = trim((string) ($promo['data_fine'] ?? ''));
        if ($inizio === '' || $fine === '') {
            return '';
        }

        return 'dal ' . date_short($inizio) . ' al ' . date_short($fine);
    }

    private static function disabilitato(): bool
    {
        return !(defined('MAIL_ENABLED') ? (bool) constant('MAIL_ENABLED') : true);
    }

    private static function appName(): string
    {
        return defined('APP_NAME') ? (string) constant('APP_NAME') : 'TrackPortal';
    }
}
