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
        $prenotazioni = self::loadPrenotazioniIntervallo($circuitoId, $intervalloInizio, $intervalloFine);
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
            'griglia'          => self::costruisciGriglia($lunedi, $sessioni, $prenotazioni, $prenotazioniUtente),
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
        array $prenotazioni,
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
            $postiOccupati = self::contaPrenotazioniIntervallo(
                (string) $s['inizio'],
                (string) $s['fine'],
                $prenotazioni
            );
            $s['posti_occupati'] = $postiOccupati;
            $s['posti_liberi']   = max(0, $postiMax - $postiOccupati);

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

        foreach ($prenotazioni as $p) {
            try {
                $dtIni = new DateTimeImmutable((string) $p['inizio_sessione']);
                $dtFin = new DateTimeImmutable((string) $p['fine_sessione']);
            } catch (Exception) {
                continue;
            }

            foreach ($giorni as $giorno) {
                foreach (self::ORE_GIORNO as $ora) {
                    $slotIni = new DateTimeImmutable($giorno . ' ' . $ora . ':00');
                    $slotFin = $slotIni->modify('+1 hour');
                    if ($slotIni < $dtFin && $slotFin > $dtIni) {
                        // Marca lo slot come "prenotato" solo se non ospita già una
                        // sessione .
                        if (!in_array(
                            (string) ($griglia[$giorno][$ora]['stato'] ?? ''),
                            ESessione::CATEGORIE,
                            true
                        )) {
                            $griglia[$giorno][$ora] = [
                                'stato'        => 'prenotato',
                                'sessione'     => null,
                                'prenotazione' => $p,
                            ];
                        }
                    }
                }
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
                    $ini = (string) ($sessione['inizio'] ?? '');
                    $fin = (string) ($sessione['fine'] ?? '');
                    foreach ($prenotazioniUtente as $p) {
                        if ((string) ($p['inizio_sessione'] ?? '') < $fin
                            && (string) ($p['fine_sessione'] ?? '') > $ini) {
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

    /** Conta il numero di prenotazioni in un intervallo di tempo */
    private static function contaPrenotazioniIntervallo(
        string $inizio,
        string $fine,
        array $prenotazioni
    ): int {
        $occupati = 0;
        foreach ($prenotazioni as $p) {
            if ((string) ($p['inizio_sessione'] ?? '') < $fine
                && (string) ($p['fine_sessione'] ?? '') > $inizio) {
                $occupati++;
            }
        }

        return $occupati;
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

    /** query per caricare le sessioni di un circuito in un intervallo di tempo */
    public static function loadByCircuitoIntervallo(int $circuitoId, string $inizio, string $fine): array
    {
        return FDataBase::executeQuery(
            "SELECT * FROM sessione
             WHERE circuito_id = :c
               AND stato <> 'annullata'
               AND inizio < :fine AND fine > :inizio
             ORDER BY inizio ASC",
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

    /** query per caricare le prenotazioni in un intervallo di tempo */
    public static function loadPrenotazioniIntervallo(int $circuitoId, string $inizio, string $fine): array
    {
        return FDataBase::executeQuery(
            "SELECT id, inizio_sessione, fine_sessione, stato
             FROM prenotazione
             WHERE circuito_id = :c
               AND stato = 'confermata'
               AND inizio_sessione < :fine AND fine_sessione > :inizio
             ORDER BY inizio_sessione ASC",
            [':c' => $circuitoId, ':inizio' => $inizio, ':fine' => $fine]
        )->fetchAllAssociative();
    }
}