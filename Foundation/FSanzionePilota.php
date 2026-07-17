<?php

/**
 * Repository per le sanzioni emesse dai gestori verso i piloti.
 */
class FSanzionePilota extends FRepository
{
    protected static function entityClass(): string
    {
        return ESanzionePilota::class;
    }

    /**
     * Tipo di emittente della sanzione (gestore circuito ).
     */ 
    protected static function emittenteTipo(): string
    {
        return ESanzionePilota::EMITTENTE_GESTORE;
    }

    /**
     * Restituisce la sanzione attiva (operativa) che blocca il pilota su un circuito, se esiste.
     */
    public static function pilotaBloccatoSuCircuito(int $pilotaId, int $circuitoId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT s.*
             FROM sanzione_pilota s
             JOIN circuito c ON c.gestore_id = s.gestore_id
             WHERE c.id = :circuito
               AND s.pilota_id = :pilota
               AND s.stato = 'attiva'
               AND s.data_inizio <= CURDATE()
               AND (s.tipo = 'ban' OR s.data_fine >= CURDATE())
             ORDER BY (s.tipo = 'ban') DESC, s.data_fine DESC
             LIMIT 1",
            [':circuito' => $circuitoId, ':pilota' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /**
     * Restituisce la sanzione attiva (operativa) che blocca il pilota da parte del gestore, se esiste.
     */
    public static function loadAttivaByGestorePilota(int $gestoreId, int $pilotaId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT *
             FROM sanzione_pilota
             WHERE gestore_id = :g
               AND pilota_id = :p
               AND stato = 'attiva'
               AND (tipo = 'ban' OR data_fine >= CURDATE())
             ORDER BY (tipo = 'ban') DESC, data_fine DESC
             LIMIT 1",
            [':g' => $gestoreId, ':p' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /**
     * Restituisce tutte le sanzioni emesse dal gestore verso i piloti, con i dati del pilota.
     */
    public static function loadByGestore(int $gestoreId): array
    {
        $rows = FDataBase::executeQuery(
            "SELECT s.*,
                    u.nome AS pilota_nome, u.cognome AS pilota_cognome, u.email AS pilota_email
             FROM sanzione_pilota s
             JOIN account u ON u.id = s.pilota_id
             WHERE s.gestore_id = :g
             ORDER BY (s.stato = 'attiva') DESC, s.data_creazione DESC, s.id DESC",
            [':g' => $gestoreId]
        )->fetchAllAssociative();

        $oggi = (new DateTimeImmutable('today'))->format('Y-m-d');
        foreach ($rows as &$r) {
            $r['stato_effettivo'] = self::statoEffettivo($r, $oggi);
        }
        unset($r);

        return $rows;
    }

    /**
     * Restituisce l'elenco dei piloti che hanno prenotato su un circuito del gestore e che non hanno già una sanzione operativa in corso.
     */
    public static function pilotiSanzionabili(int $gestoreId): array
    {
        return FDataBase::executeQuery(
            "SELECT DISTINCT u.id, u.nome, u.cognome, u.email
             FROM prenotazione p
             JOIN circuito c ON c.id = p.circuito_id
             JOIN account u ON u.id = p.pilota_id
             WHERE c.gestore_id = :g
               AND NOT EXISTS (
                   SELECT 1 FROM sanzione_pilota s
                   WHERE s.gestore_id = :g2
                     AND s.pilota_id = u.id
                     AND s.stato = 'attiva'
                     AND (s.tipo = 'ban' OR s.data_fine >= CURDATE())
               )
             ORDER BY u.cognome, u.nome",
            [':g' => $gestoreId, ':g2' => $gestoreId]
        )->fetchAllAssociative();
    }

    /**
     * True se il pilota può essere sanzionato dal gestore: ha prenotato su un suo
     * circuito e non ha già una sanzione operativa in corso.
     */
    public static function pilotaSanzionabile(int $gestoreId, int $pilotaId): bool
    {
        $haPrenotato = (int) FDataBase::executeQuery(
            "SELECT COUNT(*)
             FROM prenotazione p
             JOIN circuito c ON c.id = p.circuito_id
             WHERE c.gestore_id = :g AND p.pilota_id = :p",
            [':g' => $gestoreId, ':p' => $pilotaId]
        )->fetchOne();

        if ($haPrenotato < 1) {
            return false;
        }

        return self::loadAttivaByGestorePilota($gestoreId, $pilotaId) === null;
    }

    /**
     * Applica una nuova sanzione del gestore verso il pilota, annullando con rimborso del 100% le prenotazioni future 
     * coperte dalla finestra della sanzione (per il ban tutte; per la sospensione quelle entro la data di fine). 
     * Le prenotazioni già annullate non vengono ripristinate.
     */
    public static function applica(
        int $gestoreId,
        int $pilotaId,
        string $tipo,
        ?string $dataFine,
        ?string $motivo
    ): int {
        if (!in_array($tipo, ESanzionePilota::TIPI, true)) {
            throw new InvalidArgumentException('Tipo di sanzione non valido.');
        }
        $motivoNorm = trim((string) $motivo);
        if ($motivoNorm === '') {
            throw new InvalidArgumentException('Indica la motivazione del provvedimento.');
        }
        if (mb_strlen($motivoNorm) > 255) {
            throw new InvalidArgumentException('La motivazione non può superare 255 caratteri.');
        }

        $dataFineNorm = null;
        $finoA        = null; // limite superiore per lo storno (solo sospensione)
        if ($tipo === ESanzionePilota::TIPO_SOSPENSIONE) {
            $dataFineNorm = self::validaDataFine($dataFine);
            $finoA        = $dataFineNorm . ' 23:59:59';
        }

        //Risoluzione dinamica della classe (es. circuito/noleggio).
        // Usiamo $repo per assicurarci che i metodi ridefiniti siano chiamati correttamente 
        // anche all'interno della closure statica della transazione.
        $repo          = static::class;
        $causaPren     = $repo::causaStorno($tipo, $dataFineNorm);
        $emittenteTipo = $repo::emittenteTipo();

        // Evitiamo duplicati prima di avviare la transazione.
        // Se l'eccezione scattasse dentro la transazione, l'EntityManager si bloccherebbe.
        // Intercettando qui il caso "già sanzionato" (che è un flusso previsto), 
        // l'EM rimane attivo e possiamo mostrare gli errori nel form.
        if ($repo::loadAttivaByGestorePilota($gestoreId, $pilotaId) !== null) {
            throw new InvalidArgumentException('Esiste già una sanzione attiva per questo pilota.');
        }

        $stornate = [];

        $count = FPersistentManager::transaction(static function () use (
            $repo, $gestoreId, $pilotaId, $tipo, $dataFineNorm, $motivoNorm, $finoA, $causaPren, $emittenteTipo, &$stornate
        ): int {
            $sanzione = new ESanzionePilota(
                $gestoreId,
                $pilotaId,
                $tipo,
                $dataFineNorm,
                $motivoNorm,
                emittenteTipo: $emittenteTipo
            );
            FPersistentManager::persist($sanzione, false);

            $n = $repo::stornaPrenotazioniFuture($gestoreId, $pilotaId, $finoA, $causaPren, $stornate);

            FPersistentManager::flush();

            return $n;
        });

        // Storno fiscale al 100% dopo il commit (best-effort, fuori transazione),
        // coerente con l'annullamento sessione del gestore.
        self::emettiNoteCredito($stornate, $causaPren);

        return $count;
    }

    /**
     * Modifica il tipo e/o la data di fine di una sanzione attiva del gestore verso il pilota,
     *  annullando con rimborso del 100% le prenotazioni future coperte dalla nuova finestra della sanzione 
     * (per il ban tutte; per la sospensione quelle entro la data di fine). 
     * Le prenotazioni già annullate non vengono ripristinate.
     */
    public static function modificaPeriodo(
        int $sanzioneId,
        int $gestoreId,
        string $tipo,
        ?string $dataFine
    ): int {
        if (!in_array($tipo, ESanzionePilota::TIPI, true)) {
            throw new InvalidArgumentException('Tipo di sanzione non valido.');
        }

        $repo     = static::class;
        $sanzione = FPersistentManager::find(ESanzionePilota::class, $sanzioneId);
        if (!$sanzione instanceof ESanzionePilota
            || $sanzione->getGestoreId() !== $gestoreId
            || $sanzione->getEmittenteTipo() !== $repo::emittenteTipo()
            || $sanzione->getStato() !== ESanzionePilota::STATO_ATTIVA) {
            throw new InvalidArgumentException(
                'Sanzione non trovata, non di tua competenza o non attiva.'
            );
        }

        $dataFineNorm = null;
        $finoA        = null;
        if ($tipo === ESanzionePilota::TIPO_SOSPENSIONE) {
            $dataFineNorm = self::validaDataFine($dataFine);
            $finoA        = $dataFineNorm . ' 23:59:59';
        }

        $causaPren = $repo::causaStorno($tipo, $dataFineNorm);
        $pilotaId  = $sanzione->getPilotaId();
        $stornate  = [];

        FPersistentManager::transaction(static function () use (
            $repo, $sanzione, $tipo, $dataFineNorm, $gestoreId, $pilotaId, $finoA, $causaPren, &$stornate
        ): void {
            $sanzione->setTipo($tipo);
            // Il ban è permanente: la data di fine non ha senso e viene azzerata.
            $sanzione->setDataFine($tipo === ESanzionePilota::TIPO_BAN ? null : $dataFineNorm);

            $repo::stornaPrenotazioniFuture($gestoreId, $pilotaId, $finoA, $causaPren, $stornate);

            FPersistentManager::flush();
        });

        self::emettiNoteCredito($stornate, $causaPren);

        return count($stornate);
    }

    /**
     * Revoca una sanzione attiva del gestore verso il pilota, senza ripristinare le prenotazioni già annullate.
     */
    public static function revoca(int $sanzioneId, int $gestoreId): bool
    {
        $sanzione = FPersistentManager::find(ESanzionePilota::class, $sanzioneId);
        if (!$sanzione instanceof ESanzionePilota
            || $sanzione->getGestoreId() !== $gestoreId
            || $sanzione->getEmittenteTipo() !== static::emittenteTipo()
            || $sanzione->getStato() !== ESanzionePilota::STATO_ATTIVA) {
            return false;
        }

        $sanzione->revoca();
        FPersistentManager::flush();

        return true;
    }

    /**
     * Annulla le prenotazioni future del pilota sul gestore, fino alla data indicata (o tutte se null),
     */
    protected static function stornaPrenotazioniFuture(
        int $gestoreId,
        int $pilotaId,
        ?string $finoA,
        string $causaPren,
        array &$stornate
    ): int {
        $n = 0;
        foreach (static::prenotazioniFutureDaStornare($gestoreId, $pilotaId, $finoA) as $row) {
            $pren = FPersistentManager::find(EPrenotazione::class, (int) ($row['id'] ?? 0));
            if (!$pren instanceof EPrenotazione || $pren->getStato() !== 'confermata') {
                continue;
            }
            $pren->setStato('cancellata');
            $pren->setCausaCancellazione($causaPren);
            $pren->setRimborsoPrevisto($pren->getPrezzoImporto());
            $stornate[] = ['id' => (int) $pren->getId(), 'importo' => $pren->getPrezzoImporto()];
            $n++;
        }

        return $n;
    }