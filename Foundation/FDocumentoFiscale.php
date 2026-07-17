<?php

/**
 * Classe statica per la gestione dei documenti fiscali (fatture e note di credito).
 */
class FDocumentoFiscale
{
    public const EM_GESTORE_CIRCUITI = 'gestore_circuiti';
    public const EM_AZIENDA_NOLEGGIO = 'azienda_noleggio';
    public const EM_PIATTAFORMA      = 'piattaforma';

    public const TIPO_FATTURA      = 'fattura';
    public const TIPO_NOTA_CREDITO = 'nota_credito';

    /**
     * Restituisce il prossimo numero disponibile per un emittente e anno.
     * Se non esiste ancora un contatore per l'emittente e anno, lo crea con valore 1,
     *  altrimenti incrementa il contatore e restituisce il nuovo valore.
     */
    public static function prossimoNumero(string $emittenteTipo, int $emittenteId, int $anno): int
    {
        FDataBase::executeStatement(
            'INSERT INTO contatore_documento (emittente_tipo, emittente_id, anno, ultimo_numero)
             VALUES (:t, :i, :a, 1)
             ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero + 1',
            [':t' => $emittenteTipo, ':i' => $emittenteId, ':a' => $anno]
        );

        return (int) FDataBase::executeQuery(
            'SELECT ultimo_numero FROM contatore_documento
             WHERE emittente_tipo = :t AND emittente_id = :i AND anno = :a',
            [':t' => $emittenteTipo, ':i' => $emittenteId, ':a' => $anno]
        )->fetchOne();
    }

    /**
     * Inserisce un nuovo documento fiscale (fattura o nota di credito) e le relative righe.
     */
    public static function inserisci(array $d, array $righe): int
    {
        FDataBase::executeStatement(
            'INSERT INTO documento_fiscale
                (tipo, emittente_tipo, emittente_id, anno, numero, numero_formattato, data_emissione,
                 emittente_denominazione, emittente_piva, emittente_cf, emittente_indirizzo,
                 cliente_tipo, cliente_id, cliente_denominazione, cliente_piva, cliente_cf, cliente_indirizzo,
                 prenotazione_id, documento_riferimento_id, causale, valuta,
                 totale_imponibile, totale_iva, totale_documento, bollo)
             VALUES
                (:tipo, :em_tipo, :em_id, :anno, :numero, :numfmt, :data,
                 :em_den, :em_piva, :em_cf, :em_ind,
                 :cl_tipo, :cl_id, :cl_den, :cl_piva, :cl_cf, :cl_ind,
                 :pren, :rif, :causale, :valuta,
                 :t_imp, :t_iva, :t_doc, :bollo)',
            [
                ':tipo'    => $d['tipo'],
                ':em_tipo' => $d['emittente_tipo'],
                ':em_id'   => (int) $d['emittente_id'],
                ':anno'    => (int) $d['anno'],
                ':numero'  => (int) $d['numero'],
                ':numfmt'  => $d['numero_formattato'],
                ':data'    => $d['data_emissione'],
                ':em_den'  => $d['emittente_denominazione'],
                ':em_piva' => $d['emittente_piva'] ?? '',
                ':em_cf'   => $d['emittente_cf'] ?? null,
                ':em_ind'  => $d['emittente_indirizzo'] ?? '',
                ':cl_tipo' => $d['cliente_tipo'],
                ':cl_id'   => (int) $d['cliente_id'],
                ':cl_den'  => $d['cliente_denominazione'],
                ':cl_piva' => $d['cliente_piva'] ?? null,
                ':cl_cf'   => $d['cliente_cf'] ?? null,
                ':cl_ind'  => $d['cliente_indirizzo'] ?? '',
                ':pren'    => $d['prenotazione_id'] ?? null,
                ':rif'     => $d['documento_riferimento_id'] ?? null,
                ':causale' => $d['causale'] ?? null,
                ':valuta'  => $d['valuta'] ?? 'EUR',
                ':t_imp'   => $d['totale_imponibile'],
                ':t_iva'   => $d['totale_iva'],
                ':t_doc'   => $d['totale_documento'],
                ':bollo'   => $d['bollo'] ?? 0,
            ]
        );

        $docId = (int) FDataBase::getInstance()->lastInsertId();

        foreach ($righe as $r) {
            FDataBase::executeStatement(
                'INSERT INTO documento_fiscale_riga
                    (documento_id, descrizione, quantita, prezzo_unitario, imponibile, aliquota_iva, natura_iva, imposta)
                 VALUES (:doc, :descr, :qta, :pu, :imp, :aliq, :nat, :imposta)',
                [
                    ':doc'     => $docId,
                    ':descr'   => $r['descrizione'],
                    ':qta'     => $r['quantita'] ?? 1,
                    ':pu'      => $r['prezzo_unitario'] ?? $r['imponibile'],
                    ':imp'     => $r['imponibile'],
                    ':aliq'    => $r['aliquota_iva'] ?? 0,
                    ':nat'     => $r['natura_iva'] ?? null,
                    ':imposta' => $r['imposta'] ?? 0,
                ]
            );
        }

        return $docId;
    }

    public static function esistonoPerPrenotazione(int $prenId, ?string $tipo = null): bool
    {
        $sql  = 'SELECT COUNT(*) FROM documento_fiscale WHERE prenotazione_id = :p';
        $args = [':p' => $prenId];
        if ($tipo !== null) {
            $sql .= ' AND tipo = :t';
            $args[':t'] = $tipo;
        }

        return ((int) FDataBase::executeQuery($sql, $args)->fetchOne()) > 0;
    }

    public static function notaCreditoEsiste(int $fatturaId): bool
    {
        return ((int) FDataBase::executeQuery(
            'SELECT COUNT(*) FROM documento_fiscale
             WHERE tipo = :t AND documento_riferimento_id = :rif',
            [':t' => self::TIPO_NOTA_CREDITO, ':rif' => $fatturaId]
        )->fetchOne()) > 0;
    }

    /**
     * Restituisce i dati del documento fiscale (fattura o nota di credito) con le righe.
     */
    public static function loadById(int $id): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT * FROM documento_fiscale WHERE id = :id',
            [':id' => $id]
        )->fetchAssociative();

        return $r ?: null;
    }

    
    public static function loadRighe(int $documentoId): array
    {
        return FDataBase::executeQuery(
            'SELECT * FROM documento_fiscale_riga WHERE documento_id = :d ORDER BY id',
            [':d' => $documentoId]
        )->fetchAllAssociative();
    }

    /**
     * Restituisce le fatture di un pilota per un insieme di prenotazioni.
     */
    public static function fatturePilotaPerPrenotazioni(array $prenIds, int $pilotaId): array
    {
        $ids = array_values(array_filter(array_map('intval', $prenIds), static fn (int $v): bool => $v > 0));
        if ($ids === []) {
            return [];
        }

        $inList = implode(',', $ids);
        $rows = FDataBase::executeQuery(
            "SELECT id, prenotazione_id, emittente_tipo, numero_formattato
             FROM documento_fiscale
             WHERE tipo = :t AND cliente_tipo = 'pilota' AND cliente_id = :c
               AND prenotazione_id IN ({$inList})
             ORDER BY prenotazione_id, id",
            [':t' => self::TIPO_FATTURA, ':c' => $pilotaId]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['prenotazione_id']][] = [
                'id'                => (int) $r['id'],
                'emittente_tipo'    => (string) $r['emittente_tipo'],
                'numero_formattato' => (string) $r['numero_formattato'],
            ];
        }

        return $map;
    }

    /**
     * Restituisce le fatture di una prenotazione.
     */
    public static function loadFatturePerPrenotazione(int $prenId): array
    {
        return FDataBase::executeQuery(
            'SELECT * FROM documento_fiscale
             WHERE prenotazione_id = :p AND tipo = :t
             ORDER BY id',
            [':p' => $prenId, ':t' => self::TIPO_FATTURA]
        )->fetchAllAssociative();
    }

    /**
     * Verifica che l'utente sia emittente o destinatario del documento (autorizzazione al download).
     */
    public static function utenteAutorizzato(array $doc, string $ruolo, int $id): bool
    {
        $emittente = match ($ruolo) {
            EGestoreCircuiti::$ruolo => $doc['emittente_tipo'] === 'gestore_circuiti' && (int) $doc['emittente_id'] === $id,
            EGestoreNoleggio::$ruolo => $doc['emittente_tipo'] === 'azienda_noleggio' && (int) $doc['emittente_id'] === $id,
            default                  => false,
        };

        $clienteTipo = match ($ruolo) {
            EPilota::$ruolo          => 'pilota',
            EGestoreCircuiti::$ruolo => 'gestore_circuiti',
            EGestoreNoleggio::$ruolo => 'azienda_noleggio',
            default                  => '',
        };
        $cliente = $clienteTipo !== '' && $doc['cliente_tipo'] === $clienteTipo && (int) $doc['cliente_id'] === $id;

        return $emittente || $cliente;
    }
}