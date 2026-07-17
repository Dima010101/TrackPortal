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