<?php

/** Repository sessioni di pista */
class FSessione extends FRepository
{
    /** fasce orarie prenotabili  */
    public const ORE_GIORNO = [
        '08:00', '09:00', '10:00', '11:00', '12:00',
        '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
    ];

    protected static function entityClass(): string
    {
        return ESessione::class;
    }

    /**Metodo che restituisce i dati della griglia settimanale di un circuito (sessioni, slot prenotati, eventuali prenotazioni).
     * Con $circuitoId non valido restituisce lo scheletro vuoto della settimana.
     */
    public static function calendarioSettimanale(int $circuitoId, int $settimana = 0, ?int $pilotaId = null): array
    {
        $lunedi = self::lunediSettimana($settimana);

        if ($circuitoId < 1) {
            return [
                'lunedi'           => $lunedi,
                'griglia'          => [],
                'ore'              => self::ORE_GIORNO,
                'etichette_giorni' => [],
                'settimana'        => $settimana,
            ];
        }

        $domenica         = (new DateTimeImmutable($lunedi))->modify('+6 days')->format('Y-m-d 23:59:59');
        $intervalloInizio = $lunedi . ' 00:00:00';
        $intervalloFine   = $domenica;

        $sessioni     = self::loadByCircuitoIntervallo($circuitoId, $intervalloInizio, $intervalloFine);
        $prenotazioniUtente = ($pilotaId !== null && $pilotaId > 0)
            ? FPrenotazione::loadAttiveByPilotaIntervallo($pilotaId, $circuitoId, $intervalloInizio, $intervalloFine)
            : [];

        $lun             = new DateTimeImmutable($lunedi);
        $etichetteGiorni = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $lun->modify('+' . $i . ' days');
            $etichetteGiorni[] = [
                'data'  => $d->format('Y-m-d'),
                'label' => $d->format('D d/m'),
            ];
        }

        return [
            'lunedi'           => $lunedi,
            'griglia'          => self::costruisciGriglia($lunedi, $sessioni, $prenotazioniUtente),
            'ore'              => self::ORE_GIORNO,
            'etichette_giorni' => $etichetteGiorni,
            'settimana'        => $settimana,
        ];
    }

    /** Lunedì (Y-m-d) della settimana corrente spostata di $offsetSettimane. */
    private static function lunediSettimana(int $offsetSettimane): string
    {
        $base = new DateTimeImmutable('monday this week');
        if ($offsetSettimane !== 0) {
            $base = $base->modify(($offsetSettimane > 0 ? '+' : '') . $offsetSettimane . ' weeks');
        }

        return $base->format('Y-m-d');
    }

    /** Costruisce la griglia settimanale con le sessioni e le prenotazioni */
    private static function costruisciGriglia(
        string $lunedi,
        array $sessioni,
        array $prenotazioniUtente = []
    ): array {
        $giorni = [];
        $lun    = new DateTimeImmutable($lunedi);
        for ($i = 0; $i < 7; $i++) {
            $giorni[] = $lun->modify('+' . $i . ' days')->format('Y-m-d');
        }

        $griglia = [];
        foreach ($giorni as $giorno) {
            $griglia[$giorno] = [];
            foreach (self::ORE_GIORNO as $ora) {
                $griglia[$giorno][$ora] = [
                    'stato'    => 'libero',
                    'sessione' => null,
                ];
            }
        }

        foreach ($sessioni as $s) {
            try {
                $dtIni = new DateTimeImmutable((string) $s['inizio']);
                $dtFin = new DateTimeImmutable((string) $s['fine']);
            } catch (Exception) {
                continue;
            }

            $postiMax      = (int) ($s['posti_max'] ?? 1);
            $postiOccupati = (int) ($s['posti_occupati'] ?? 0);
            $s['posti_liberi'] = max(0, $postiMax - $postiOccupati);

            // la cella iniziale di una sessione viene resa con un
            // rowspan pari al numero di ore coperte così il
            // riquadro ricopre tutte le fasce; le celle successive sono marcate come
            // "continuazione" e non vengono renderizzate
            $celle   = [];
            $cursore = $dtIni;
            while ($cursore < $dtFin) {
                $g = $cursore->format('Y-m-d');
                $o = $cursore->format('H:i');
                if (isset($griglia[$g][$o])) {
                    $celle[] = [$g, $o];
                }
                $cursore = $cursore->modify('+1 hour');
            }

            $rowspan = count($celle);
            foreach ($celle as $i => [$g, $o]) {
                $griglia[$g][$o] = [
                    'stato'         => (string) ($s['stato'] ?? 'privata'),
                    'sessione'      => $s,
                    'continuazione' => $i > 0,
                    'rowspan'       => $i === 0 ? max(1, $rowspan) : 1,
                ];
            }
        }

        if ($prenotazioniUtente !== []) {
            foreach ($griglia as $giorno => &$oreGiorno) {
                foreach ($oreGiorno as $ora => &$cell) {
                    $st = (string) ($cell['stato'] ?? '');
                    if (!in_array($st, ESessione::CATEGORIE, true)) {
                        continue;
                    }
                    $sessione = $cell['sessione'] ?? null;
                    if (!is_array($sessione)) {
                        continue;
                    }
                    foreach ($prenotazioniUtente as $p) {
                        if ((int) ($p['sessione_id'] ?? 0) === (int) ($sessione['id'] ?? 0)) {
                            $cell['mia_prenotazione'] = true;
                            $cell['prenotazione']     = $p;
                            break;
                        }
                    }
                }
            }
            unset($oreGiorno, $cell);
        }

        return $griglia;
    }

    public static function store(ESessione $s): int
    {
        return self::persistAndId($s);
    }

    /** load di singola entità: ritorna l'oggetto ESessione*/
    public static function loadById(int $id): ?ESessione
    {
        return self::loadEntityById($id);
    }

    public static function loadEntityById(int $id): ?ESessione
    {
        $entity = FPersistentManager::find(ESessione::class, $id);
        return $entity instanceof ESessione ? $entity : null;
    }

    /** query per caricare le sessioni di un circuito in un intervallo di tempo, con i posti occupati */
    public static function loadByCircuitoIntervallo(int $circuitoId, string $inizio, string $fine): array
    {
        return FDataBase::executeQuery(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM prenotazione p
                     WHERE p.sessione_id = s.id AND p.stato = 'confermata') AS posti_occupati
             FROM sessione s
             WHERE s.circuito_id = :c
               AND s.stato <> 'annullata'
               AND s.inizio < :fine AND s.fine > :inizio
             ORDER BY s.inizio ASC",
            [':c' => $circuitoId, ':inizio' => $inizio, ':fine' => $fine]
        )->fetchAllAssociative();
    }

    /** query per caricare una sessione per un gestore */
    public static function loadByIdForGestore(int $id, int $gestoreId): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT s.*, c.nome_circuito, c.gestore_id
             FROM sessione s
             JOIN circuito c ON c.id = s.circuito_id
             WHERE s.id = :id AND c.gestore_id = :g',
            [':id' => $id, ':g' => $gestoreId]
        )->fetchAssociative();

        return $r ?: null;
    }

    /** Verifica se esiste un conflitto di orari con altre sessioni */
    public static function esisteConflitto(
        int $circuitoId,
        string $inizio,
        string $fine,
        ?int $escludiSessioneId = null
    ): bool {
        $sql = "SELECT COUNT(*) FROM sessione
                WHERE circuito_id = :c
                  AND stato <> 'annullata'
                  AND inizio < :fine AND fine > :inizio";
        $args = [':c' => $circuitoId, ':inizio' => $inizio, ':fine' => $fine];

        if ($escludiSessioneId !== null && $escludiSessioneId > 0) {
            $sql .= ' AND id <> :escludi';
            $args[':escludi'] = $escludiSessioneId;
        }

        return (int) FDataBase::executeQuery($sql, $args)->fetchOne() > 0;
    }

    /** Carica una sessione di un circuito in un orario preciso */
    public static function loadByCircuitoSlot(int $circuitoId, string $data, string $ora): ?array
    {
        $inizio = $data . ' ' . $ora . ':00';
        $r = FDataBase::executeQuery(
            "SELECT * FROM sessione
             WHERE circuito_id = :c AND inizio = :i AND stato <> 'annullata'
             LIMIT 1",
            [':c' => $circuitoId, ':i' => $inizio]
        )->fetchAssociative();

        return $r ?: null;
    }

    /** sessioni concluse nel mese corrente sui circuiti di un gestore.
     *  Esclude le sessioni annullate.
     */
    public static function countConcluseMeseByGestore(int $gestoreId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM sessione s
             JOIN circuito c ON c.id = s.circuito_id
             WHERE c.gestore_id = :g
               AND s.stato <> 'annullata'
               AND s.fine < NOW()
               AND s.fine >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')",
            [':g' => $gestoreId]
        )->fetchOne();
    }

    /** conta prenotazioni attive di una sessione */
    public static function countPrenotazioniAttive(int $sessioneId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione
             WHERE sessione_id = :s
               AND stato = 'confermata'",
            [':s' => $sessioneId]
        )->fetchOne();
    }

    /** verifica se un pilota ha già una prenotazione attiva sulla sessione */
    public static function pilotaHaPrenotazioneAttiva(int $pilotaId, int $sessioneId): bool
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione
             WHERE pilota_id = :p
               AND sessione_id = :s
               AND stato = 'confermata'",
            [':p' => $pilotaId, ':s' => $sessioneId]
        )->fetchOne() > 0;
    }

    /** annulla una sessione del gestore, rimborsa i piloti prenotati e marca le rispettive prenotazioni come cancellate */
    public static function annulla(int $sessioneId, int $gestoreId, string $causa): int
    {
        $causaNorm = trim($causa);
        if ($causaNorm === '') {
            throw new InvalidArgumentException("Indica una motivazione per l'annullamento della sessione.");
        }
        if (mb_strlen($causaNorm) > 255) {
            throw new InvalidArgumentException('La motivazione non può superare 255 caratteri.');
        }

        $stornate  = [];
        $causaPren = 'Sessione annullata dal gestore del circuito: ' . $causaNorm;

        $count = FPersistentManager::transaction(static function () use (
            $sessioneId, $gestoreId, $causaNorm, $causaPren, &$stornate
        ): int {
            $sessioneRow = self::loadByIdForGestore($sessioneId, $gestoreId);
            if ($sessioneRow === null) {
                throw new InvalidArgumentException('Sessione non trovata o non di tua proprietà.');
            }
            if (($sessioneRow['stato'] ?? '') === 'annullata') {
                throw new InvalidArgumentException('La sessione è già stata annullata.');
            }

            $sessione = FPersistentManager::find(ESessione::class, $sessioneId);
            if (!$sessione instanceof ESessione) {
                throw new InvalidArgumentException('Sessione non trovata.');
            }

            $n = 0;
            foreach (FPrenotazione::loadConfermateBySessione($sessioneId) as $row) {
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

            $sessione->setStato('annullata');
            $sessione->setCausaAnnullamento($causaNorm);
            FPersistentManager::flush();

            return $n;
        });

        // rimborso dopo il commit
        foreach ($stornate as $s) {
            try {
                FFatturazione::emettiNoteCreditoPerPrenotazione(
                    $s['id'],
                    $s['importo'],
                    $s['importo'],
                    $causaPren
                );
            } catch (Throwable $e) {
                error_log('Emissione note di credito (sessione) fallita: ' . $e->getMessage());
            }
        }

        return $count;
    }
}