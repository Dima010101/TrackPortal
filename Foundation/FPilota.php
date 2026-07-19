<?php

// repository pilota
class FPilota extends FRepository
{
    protected static function entityClass(): string
    {
        return EPilota::class;
    }

    public static function loadByUtente(int $uid): ?array
    {
        return self::rowById($uid);
    }

    // load singola entità, ritorna l'oggetto di dominio EPilota dove serve la logica
    public static function loadEntityByUtente(int $uid): ?EPilota
    {
        $pilota = FPersistentManager::find(EPilota::class, $uid);

        return $pilota instanceof EPilota ? $pilota : null;
    }

    // numero di piloti con documenti in attesa, solo chi li ha caricati entrambi
    public static function countPending(): int
    {
        return FPersistentManager::countWhere(
            EPilota::class,
            "e.documentiStato = :stato"
            . " AND e.certificatoMedicoPath IS NOT NULL AND e.certificatoMedicoPath <> ''"
            . " AND e.licenzaPath IS NOT NULL AND e.licenzaPath <> ''",
            ['stato' => EPilota::DOC_IN_ATTESA]
        );
    }

    // aggiorna lo stato di convalida documenti del pilota
    public static function updateDocumentiStato(int $uid, string $stato): void
    {
        $pilota = FPersistentManager::find(EPilota::class, $uid);
        if (!$pilota instanceof EPilota) {
            return;
        }
        $pilota->setDocumentiStato($stato);
        FPersistentManager::flush();
    }

    // azzera il path di un documento e riporta i documenti in attesa, ritorna il vecchio path
    public static function rimuoviDocumento(int $uid, string $tipo): ?string
    {
        $pilota = FPersistentManager::find(EPilota::class, $uid);
        if (!$pilota instanceof EPilota) {
            return null;
        }
        if ($tipo === 'certificato_medico') {
            $vecchio = $pilota->getCertificatoMedicoPath();
            $pilota->setCertificatoMedicoPath(null);
        } elseif ($tipo === 'licenza') {
            $vecchio = $pilota->getLicenzaPath();
            $pilota->setLicenzaPath(null);
        } else {
            return null;
        }
        $pilota->setDocumentiStato(EPilota::DOC_IN_ATTESA);
        FPersistentManager::flush();

        return $vecchio;
    }

    // aggiorna i dati del profilo pilota, gli indirizzi solo se $indirizzi è valorizzato
    public static function updateProfilo(
        int $uid,
        string $categoria,
        ?string $licenza,
        ?string $scadenzaLicenza,
        array $indirizzi = []
    ): void {
        $pilota = FPersistentManager::find(EPilota::class, $uid);
        if (!$pilota instanceof EPilota) {
            return;
        }
        $pilota->setCategoria($categoria);
        $pilota->setLicenza($licenza);
        $pilota->setScadenzaLicenza($scadenzaLicenza);

        if ($indirizzi !== []) {
            if (trim((string) ($indirizzi['codice_fiscale'] ?? '')) !== '') {
                $pilota->setCodiceFiscale(codice_fiscale_normalizza((string) $indirizzi['codice_fiscale']));
            }
            $pilota->setIndirizzo((string) ($indirizzi['indirizzo'] ?? ''));
            $pilota->setCap((string) ($indirizzi['cap'] ?? ''));
            $pilota->setComune((string) ($indirizzi['comune'] ?? ''));
            $pilota->setProvincia((string) ($indirizzi['provincia'] ?? ''));
            $pilota->setFattIndirizzo((string) ($indirizzi['fatt_indirizzo'] ?? ''));
            $pilota->setFattCap((string) ($indirizzi['fatt_cap'] ?? ''));
            $pilota->setFattComune((string) ($indirizzi['fatt_comune'] ?? ''));
            $pilota->setFattProvincia((string) ($indirizzi['fatt_provincia'] ?? ''));
        }

        FPersistentManager::flush();
    }

    // aggiorna i path dei documenti PDF, ogni path solo se passato non-null
    public static function aggiornaDocumenti(int $uid, ?string $certPath, ?string $licPath): void
    {
        $pilota = FPersistentManager::find(EPilota::class, $uid);
        if (!$pilota instanceof EPilota) {
            return;
        }
        if ($certPath !== null) {
            $pilota->setCertificatoMedicoPath($certPath);
        }
        if ($licPath !== null) {
            $pilota->setLicenzaPath($licPath);
        }
        FPersistentManager::flush();
    }

    // piloti idonei a una promozione, attivi con documenti approvati ed email confermata
    public static function loadIdoneiPerPromozione(array $promo): array
    {
        $circuitoId = (int) ($promo['circuito_id'] ?? 0);

        // promozione su un veicolo, il circuito di riferimento è quello del veicolo
        if ($circuitoId < 1 && !empty($promo['veicolo_noleggio_id'])) {
            $circuitoId = (int) FDataBase::executeQuery(
                'SELECT circuito_id FROM veicolo_noleggio WHERE id = :v',
                [':v' => (int) $promo['veicolo_noleggio_id']]
            )->fetchOne();
        }

        $segmento = (string) ($promo['segmento_destinatari'] ?? 'tutti');
        $soglia   = max(
            (int) ($promo['soglia_prenotazioni'] ?? 0),
            $segmento === 'storico_prenotazioni' ? 1 : 0
        );

        $where = [
            "u.stato_account = 'attivo'",
            'u.email_verificata = 1',
            "p.documenti_stato = '" . EPilota::DOC_APPROVATI . "'",
        ];
        $args = [];

        if ($circuitoId > 0) {
            $where[] = '(SELECT COUNT(*) FROM prenotazione pr
                         JOIN sessione se ON se.id = pr.sessione_id
                         WHERE pr.pilota_id = p.id AND se.circuito_id = :circuito
                           AND pr.stato <> \'cancellata\') >= :minBookings';
            $args[':circuito']    = $circuitoId;
            $args[':minBookings'] = max(1, $soglia);
        }

        return FDataBase::executeQuery(
            'SELECT u.nome, u.cognome, u.email
             FROM pilota p
             JOIN account u ON u.id = p.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY u.cognome, u.nome',
            $args
        )->fetchAllAssociative();
    }
}
