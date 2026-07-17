<?php

/**
 * Classe di utilità per la gestione della fatturazione.
 */
class FFatturazione
{
    // Trattamenti IVA speciali — default, da validare fiscalmente.
    private const ASSICURAZIONE_NATURA = 'N4';   // esente art. 10 DPR 633/72
    private const PENALE_NATURA        = 'N2.2';  // penale risarcitoria fuori campo (art. 15)
    private const BOLLO_SOGLIA         = 77.47;
    private const BOLLO_IMPORTO        = 2.00;

    /**
     * Emette i documenti fiscali per una prenotazione.
     */
    public static function emettiPerPrenotazione(int $prenId): void
    {
        if (FDocumentoFiscale::esistonoPerPrenotazione($prenId)) {
            return;
        }

        $p = self::datiPrenotazione($prenId);
        if ($p === null) {
            return;
        }

        $config    = FConfigurazionePiattaforma::carica();
        $aliquota  = $config->getAliquotaIva();
        $valuta    = (string) ($p['prezzo_valuta'] ?? 'EUR');
        $periodo   = self::formatPeriodo((string) $p['inizio_sessione'], (string) $p['fine_sessione']);
        $codice    = (string) $p['codice_identificativo'];

        // Indirizzi del pilota
        $indFatturazione = self::formatIndirizzo($p['pil_fatt_ind'], $p['pil_fatt_cap'], $p['pil_fatt_comune'], $p['pil_fatt_prov']);
        $indResidenza    = self::formatIndirizzo($p['pil_ind'], $p['pil_cap'], $p['pil_comune'], $p['pil_prov']);

        $clientePilota = [
            'cliente_tipo'          => 'pilota',
            'cliente_id'            => (int) $p['pilota_id'],
            'cliente_denominazione' => trim(($p['pil_nome'] ?? '') . ' ' . ($p['pil_cognome'] ?? '')),
            'cliente_cf'            => $p['pil_cf'] ?: null,
            'cliente_piva'          => null,
            'cliente_indirizzo'     => $indFatturazione !== '' ? $indFatturazione : $indResidenza,
        ];

        $emittenteGestore = [
            'emittente_tipo'          => FDocumentoFiscale::EM_GESTORE_CIRCUITI,
            'emittente_id'            => (int) $p['gestore_id'],
            'emittente_denominazione' => (string) $p['gc_societa'],
            'emittente_piva'          => (string) $p['gc_piva'],
            'emittente_cf'            => $p['gc_cf'] ?: null,
            'emittente_indirizzo'     => self::formatIndirizzo($p['gc_ind'], $p['gc_cap'], $p['gc_comune'], $p['gc_prov']),
        ];

        $emittentePiattaforma = self::emittentePiattaforma($config);

        FPersistentManager::transaction(static function () use (
            $p, $aliquota, $valuta, $periodo, $codice, $clientePilota,
            $emittenteGestore, $emittentePiattaforma, $config
        ): void {
            // 1) Accesso pista: gestore circuiti -> pilota
            if ((float) $p['imponibile_accesso'] > 0) {
                self::creaDocumento(array_merge($emittenteGestore, $clientePilota, [
                    'tipo'            => FDocumentoFiscale::TIPO_FATTURA,
                    'prenotazione_id' => (int) $p['id'],
                    'valuta'          => $valuta,
                    'causale'         => 'Accesso pista ' . $p['nome_circuito'],
                    'righe'           => [self::riga(
                        'Accesso pista — ' . $p['nome_circuito'] . ' (' . $periodo . ')',
                        (float) $p['imponibile_accesso'],
                        $aliquota, null
                    )],
                ]));
            }

            // 2) Noleggio veicolo: gestore noleggio -> pilota
            if (!empty($p['veicolo_noleggio_id']) && (float) $p['imponibile_noleggio'] > 0) {
                $nol = self::datiNoleggio((int) $p['veicolo_noleggio_id']);
                if ($nol !== null) {
                    $descrVeicolo = trim(($nol['marca'] ?? '') . ' ' . ($nol['modello'] ?? ''))
                        . ' — targa ' . ($nol['targa'] ?? '');
                    self::creaDocumento([
                        'tipo'                    => FDocumentoFiscale::TIPO_FATTURA,
                        'emittente_tipo'          => FDocumentoFiscale::EM_AZIENDA_NOLEGGIO,
                        'emittente_id'            => (int) $nol['azienda_id'],
                        'emittente_denominazione' => (string) $nol['an_societa'],
                        'emittente_piva'          => (string) $nol['an_piva'],
                        'emittente_cf'            => $nol['an_cf'] ?: null,
                        'emittente_indirizzo'     => self::formatIndirizzo($nol['an_ind'], $nol['an_cap'], $nol['an_comune'], $nol['an_prov']),
                        'prenotazione_id'         => (int) $p['id'],
                        'valuta'                  => $valuta,
                        'causale'                 => 'Noleggio veicolo',
                        'righe'                   => [self::riga(
                            'Noleggio veicolo ' . $descrVeicolo . ' (' . $periodo . ')',
                            (float) $p['imponibile_noleggio'],
                            $aliquota, null
                        )],
                    ] + $clientePilota);
                }
            }

            // 3) Assicurazione: piattaforma -> pilota (esente art. 10)
            if (!empty($p['assicurazione']) && (float) $p['imponibile_assicurazione'] > 0) {
                self::creaDocumento(array_merge($emittentePiattaforma, $clientePilota, [
                    'tipo'            => FDocumentoFiscale::TIPO_FATTURA,
                    'prenotazione_id' => (int) $p['id'],
                    'valuta'          => $valuta,
                    'causale'         => 'Copertura assicurativa',
                    'righe'           => [self::riga(
                        'Copertura assicurativa sessione (' . $periodo . ')',
                        (float) $p['imponibile_assicurazione'],
                        0.0, self::ASSICURAZIONE_NATURA
                    )],
                ]));
            }

            // 4) Commissione: piattaforma -> gestore circuiti (sull'accesso pista)
            $perc        = $config->getPercentualeCommissione();
            $impCommiss  = round((float) $p['imponibile_accesso'] * $perc / 100, 2);
            if ($impCommiss > 0) {
                self::creaDocumento(array_merge($emittentePiattaforma, [
                    'tipo'                  => FDocumentoFiscale::TIPO_FATTURA,
                    'cliente_tipo'          => 'gestore_circuiti',
                    'cliente_id'            => (int) $p['gestore_id'],
                    'cliente_denominazione' => (string) $p['gc_societa'],
                    'cliente_piva'          => (string) $p['gc_piva'],
                    'cliente_cf'            => $p['gc_cf'] ?: null,
                    'cliente_indirizzo'     => self::formatIndirizzo($p['gc_ind'], $p['gc_cap'], $p['gc_comune'], $p['gc_prov']),
                    'prenotazione_id'       => (int) $p['id'],
                    'valuta'                => $valuta,
                    'causale'               => 'Commissione servizio di prenotazione',
                    'righe'                 => [self::riga(
                        'Commissione servizio di prenotazione ' . $codice . ' (' . rtrim(rtrim(number_format($perc, 2, '.', ''), '0'), '.') . '%)',
                        $impCommiss,
                        $aliquota, null
                    )],
                ]));
            }
        });
    }

     /**
     * Emette le note di credito per una prenotazione, in caso di rimborso.
     */
    public static function emettiNoteCreditoPerPrenotazione(
        int $prenId,
        float $rimborso,
        float $totalePagato,
        ?string $causa
    ): void {
        $fatture = FDocumentoFiscale::loadFatturePerPrenotazione($prenId);
        if ($fatture === []) {
            return;
        }

        $frazione = $totalePagato > 0 ? max(0.0, min(1.0, $rimborso / $totalePagato)) : 1.0;
        if ($frazione <= 0) {
            return;
        }

        $causaleBase = 'Storno per annullamento prenotazione' . ($causa ? ' — ' . $causa : '');

        FPersistentManager::transaction(static function () use ($fatture, $frazione, $causaleBase, $prenId): void {
            foreach ($fatture as $f) {
                if (FDocumentoFiscale::notaCreditoEsiste((int) $f['id'])) {
                    continue;
                }

                $righe = [];
                foreach (FDocumentoFiscale::loadRighe((int) $f['id']) as $r) {
                    $imp     = round((float) $r['imponibile'] * $frazione, 2);
                    $aliq    = (float) $r['aliquota_iva'];
                    $imposta = round($imp * $aliq / 100, 2);
                    $righe[] = [
                        'descrizione'     => 'Storno: ' . $r['descrizione'],
                        'quantita'        => 1,
                        'prezzo_unitario' => $imp,
                        'imponibile'      => $imp,
                        'aliquota_iva'    => $aliq,
                        'natura_iva'      => $r['natura_iva'] ?: null,
                        'imposta'         => $imposta,
                    ];
                }

                self::creaDocumento([
                    'tipo'                     => FDocumentoFiscale::TIPO_NOTA_CREDITO,
                    'emittente_tipo'           => (string) $f['emittente_tipo'],
                    'emittente_id'             => (int) $f['emittente_id'],
                    'emittente_denominazione'  => (string) $f['emittente_denominazione'],
                    'emittente_piva'           => (string) $f['emittente_piva'],
                    'emittente_cf'             => $f['emittente_cf'] ?: null,
                    'emittente_indirizzo'      => (string) $f['emittente_indirizzo'],
                    'cliente_tipo'             => (string) $f['cliente_tipo'],
                    'cliente_id'               => (int) $f['cliente_id'],
                    'cliente_denominazione'    => (string) $f['cliente_denominazione'],
                    'cliente_piva'             => $f['cliente_piva'] ?: null,
                    'cliente_cf'               => $f['cliente_cf'] ?: null,
                    'cliente_indirizzo'        => (string) $f['cliente_indirizzo'],
                    'prenotazione_id'          => $prenId,
                    'documento_riferimento_id' => (int) $f['id'],
                    'valuta'                   => (string) $f['valuta'],
                    'causale'                  => $causaleBase . ' (rif. ' . $f['numero_formattato'] . ')',
                    'righe'                    => $righe,
                ]);
            }
        });
    }

    /**
     * Crea una riga di fattura o nota di credito.
    */
    private static function riga(
        string $descrizione,
        float $imponibile,
        float $aliquotaStd,
        ?string $naturaSpeciale
    ): array {
        $imponibile = round($imponibile, 2);

        if ($naturaSpeciale !== null) {
            $aliquota = 0.0;
            $natura   = $naturaSpeciale;
            $imposta  = 0.0;
        } else {
            $aliquota = $aliquotaStd;
            $natura   = null;
            $imposta  = round($imponibile * $aliquota / 100, 2);
        }

        return [
            'descrizione'     => $descrizione,
            'quantita'        => 1,
            'prezzo_unitario' => $imponibile,
            'imponibile'      => $imponibile,
            'aliquota_iva'    => $aliquota,
            'natura_iva'      => $natura,
            'imposta'         => $imposta,
        ];
    }

    /**
     * Crea un documento fiscale (fattura o nota di credito) con le righe specificate.
     */
    private static function creaDocumento(array $opts): void
    {
        $righe = $opts['righe'] ?? [];
        if ($righe === []) {
            return;
        }

        $anno   = (int) date('Y');
        $numero = FDocumentoFiscale::prossimoNumero((string) $opts['emittente_tipo'], (int) $opts['emittente_id'], $anno);

        $totImp  = 0.0;
        $totIva  = 0.0;
        $haNatura = false;
        foreach ($righe as $r) {
            $totImp += (float) $r['imponibile'];
            $totIva += (float) $r['imposta'];
            if (!empty($r['natura_iva'])) {
                $haNatura = true;
            }
        }
        $totImp = round($totImp, 2);
        $totIva = round($totIva, 2);

        // Se l'IVA è zero e ci sono righe con natura speciale, e l'imponibile supera la soglia, applica il bollo.
        $bollo = ($totIva == 0.0 && $haNatura && $totImp > self::BOLLO_SOGLIA) ? self::BOLLO_IMPORTO : 0.0;

        FDocumentoFiscale::inserisci([
            'tipo'                     => $opts['tipo'],
            'emittente_tipo'           => $opts['emittente_tipo'],
            'emittente_id'             => $opts['emittente_id'],
            'anno'                     => $anno,
            'numero'                   => $numero,
            'numero_formattato'        => $anno . '/' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT),
            'data_emissione'           => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            'emittente_denominazione'  => $opts['emittente_denominazione'],
            'emittente_piva'           => $opts['emittente_piva'] ?? '',
            'emittente_cf'             => $opts['emittente_cf'] ?? null,
            'emittente_indirizzo'      => $opts['emittente_indirizzo'] ?? '',
            'cliente_tipo'             => $opts['cliente_tipo'],
            'cliente_id'               => $opts['cliente_id'],
            'cliente_denominazione'    => $opts['cliente_denominazione'],
            'cliente_piva'             => $opts['cliente_piva'] ?? null,
            'cliente_cf'               => $opts['cliente_cf'] ?? null,
            'cliente_indirizzo'        => $opts['cliente_indirizzo'] ?? '',
            'prenotazione_id'          => $opts['prenotazione_id'] ?? null,
            'documento_riferimento_id' => $opts['documento_riferimento_id'] ?? null,
            'causale'                  => $opts['causale'] ?? null,
            'valuta'                   => $opts['valuta'] ?? 'EUR',
            'totale_imponibile'        => $totImp,
            'totale_iva'               => $totIva,
            'totale_documento'         => round($totImp + $totIva, 2),
            'bollo'                    => $bollo,
        ], $righe);
    }

    /**
     * Restituisce i dati dell'emittente per la piattaforma.
     */
    private static function emittentePiattaforma(EConfigurazionePiattaforma $config): array
    {
        return [
            'emittente_tipo'          => FDocumentoFiscale::EM_PIATTAFORMA,
            'emittente_id'            => 0,
            'emittente_denominazione' => $config->getFiscDenominazione(),
            'emittente_piva'          => $config->getFiscPartitaIva(),
            'emittente_cf'            => $config->getFiscCodiceFiscale(),
            'emittente_indirizzo'     => $config->getFiscIndirizzo(),
        ];
    }

    /**
     * Restituisce i dati della prenotazione, del pilota e del gestore circuito.
     */
    private static function datiPrenotazione(int $prenId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT p.id, p.pilota_id, p.codice_identificativo, p.prezzo_valuta,
                    p.imponibile_accesso, p.imponibile_noleggio, p.imponibile_assicurazione,
                    p.inizio_sessione, p.fine_sessione, p.assicurazione, p.veicolo_noleggio_id,
                    c.nome_circuito, c.gestore_id,
                    gc.nome_societa AS gc_societa, gc.partita_iva AS gc_piva, gc.codice_fiscale AS gc_cf,
                    gc.indirizzo AS gc_ind, gc.cap AS gc_cap, gc.comune AS gc_comune, gc.provincia AS gc_prov,
                    up.nome AS pil_nome, up.cognome AS pil_cognome,
                    pil.codice_fiscale AS pil_cf, pil.indirizzo AS pil_ind, pil.cap AS pil_cap,
                    pil.comune AS pil_comune, pil.provincia AS pil_prov,
                    pil.fatt_indirizzo AS pil_fatt_ind, pil.fatt_cap AS pil_fatt_cap,
                    pil.fatt_comune AS pil_fatt_comune, pil.fatt_provincia AS pil_fatt_prov
             FROM prenotazione p
             JOIN circuito c ON c.id = p.circuito_id
             JOIN gestore_circuiti gc ON gc.id = c.gestore_id
             JOIN account up ON up.id = p.pilota_id
             JOIN pilota pil ON pil.id = p.pilota_id
             WHERE p.id = :id",
            [':id' => $prenId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /**
     * Restituisce i dati del veicolo a noleggio e della relativa azienda di noleggio.
     */
    private static function datiNoleggio(int $veicoloId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT v.marca, v.modello, v.targa, v.azienda_id,
                    an.nome_societa AS an_societa, an.partita_iva AS an_piva, an.codice_fiscale AS an_cf,
                    an.indirizzo AS an_ind, an.cap AS an_cap, an.comune AS an_comune, an.provincia AS an_prov
             FROM veicolo_noleggio v
             JOIN azienda_noleggio an ON an.id = v.azienda_id
             WHERE v.id = :vid",
            [':vid' => $veicoloId]
        )->fetchAssociative();

        return $r ?: null;
    }

    private static function formatIndirizzo(?string $ind, ?string $cap, ?string $comune, ?string $prov): string
    {
        $via    = trim((string) $ind);
        $citta  = trim(trim((string) $cap) . ' ' . trim((string) $comune));
        $provS  = trim((string) $prov);
        if ($provS !== '') {
            $citta = trim($citta . ' (' . $provS . ')');
        }

        return trim(implode(' · ', array_filter([$via, $citta])));
    }

    private static function formatPeriodo(string $inizio, string $fine): string
    {
        $i = strtotime($inizio);
        $f = strtotime($fine);
        if ($i === false || $f === false) {
            return '';
        }

        return 'dal ' . date('d/m/Y H:i', $i) . ' al ' . date('d/m/Y H:i', $f);
    }
}