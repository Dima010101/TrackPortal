<?php

/** Repository veicoli a noleggio. */
class FVeicoloNoleggio extends FRepository
{
    protected static function entityClass(): string
    {
        return EVeicoloNoleggio::class;
    }

    public static function store(EVeicoloNoleggio $v): int
    {
        $v->setTarga(strtoupper($v->getTarga()));
        return self::persistAndId($v);
    }

    public static function loadByAzienda(int $aziendaId, ?string $categoria = null): array
    {
        $sql = "SELECT v.*, c.nome_circuito,
                       (SELECT COUNT(*) FROM prenotazione p WHERE p.veicolo_noleggio_id = v.id) AS pren_count
                FROM veicolo_noleggio v
                JOIN circuito c ON c.id = v.circuito_id
                WHERE v.azienda_id = :a";
        $args = [':a' => $aziendaId];
        if ($categoria !== null && in_array($categoria, ['auto', 'moto'], true)) {
            $sql .= ' AND v.categoria = :cat';
            $args[':cat'] = $categoria;
        }
        $sql .= ' ORDER BY c.nome_circuito, v.marca, v.modello';
        return FDataBase::executeQuery($sql, $args)->fetchAllAssociative();
    }

    /**
     * Veicoli disponibili su un circuito. Con $inizio/$fine esclude quelli già
     * prenotati (confermata) in un intervallo sovrapposto.
     */
    public static function loadDisponibiliByCircuito(int $circuitoId, ?string $inizio = null, ?string $fine = null): array
    {
        $sql = "SELECT v.*, a.nome_societa
                FROM veicolo_noleggio v
                JOIN azienda_noleggio a ON a.id = v.azienda_id
                WHERE v.circuito_id = :c AND v.disponibile = 1";
        $args = [':c' => $circuitoId];

        if ($inizio !== null && $fine !== null && $inizio !== '' && $fine !== '') {
            $sql .= " AND NOT EXISTS (
                          SELECT 1 FROM prenotazione p
                          WHERE p.veicolo_noleggio_id = v.id
                            AND p.stato = 'confermata'
                            AND p.inizio_sessione < :fine
                            AND p.fine_sessione   > :inizio
                      )";
            $args[':inizio'] = $inizio;
            $args[':fine']   = $fine;
        }

        return FDataBase::executeQuery($sql, $args)->fetchAllAssociative();
    }

    /** True se il veicolo ha una prenotazione confermata sovrapposta alla fascia. */
    public static function prenotatoInIntervallo(int $veicoloId, string $inizio, string $fine): bool
    {
        $n = FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione
             WHERE veicolo_noleggio_id = :v
               AND stato = 'confermata'
               AND inizio_sessione < :fine
               AND fine_sessione   > :inizio",
            [':v' => $veicoloId, ':fine' => $fine, ':inizio' => $inizio]
        )->fetchOne();

        return (int) $n > 0;
    }

    public static function loadTopByCircuito(int $circuitoId, int $limit = 3): array
    {
        return FDataBase::executeQuery(
            "SELECT v.id, v.marca, v.modello, v.categoria, v.targa, v.prezzo,
                    COUNT(p.id) AS prenotazioni
             FROM veicolo_noleggio v
             LEFT JOIN prenotazione p ON p.veicolo_noleggio_id = v.id
             WHERE v.circuito_id = :id
             GROUP BY v.id
             ORDER BY prenotazioni DESC, v.id
             LIMIT " . (int) $limit,
            [':id' => $circuitoId]
        )->fetchAllAssociative();
    }

    /** Veicoli più noleggiati di un'azienda. */
    public static function loadTopByAzienda(int $aziendaId, int $limit = 5): array
    {
        return FDataBase::executeQuery(
            "SELECT v.id, v.marca, v.modello, v.categoria, v.targa, v.disponibile,
                    c.nome_circuito, v.prezzo,
                    COUNT(p.id) AS prenotazioni
             FROM veicolo_noleggio v
             JOIN circuito c ON c.id = v.circuito_id
             LEFT JOIN prenotazione p ON p.veicolo_noleggio_id = v.id AND p.stato = 'confermata'
             WHERE v.azienda_id = :a
             GROUP BY v.id
             ORDER BY prenotazioni DESC, v.marca, v.modello
             LIMIT " . (int) $limit,
            [':a' => $aziendaId]
        )->fetchAllAssociative();
    }

    public static function loadById(int $id): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT v.* FROM veicolo_noleggio v WHERE v.id = :id',
            [':id' => $id]
        )->fetchAssociative();
        return $r ?: null;
    }

    public static function loadByIdAndAzienda(int $id, int $aziendaId): ?array
    {
        $r = FDataBase::executeQuery(
            "SELECT v.*, c.nome_circuito,
                    (SELECT COUNT(*) FROM prenotazione p WHERE p.veicolo_noleggio_id = v.id) AS pren_count
             FROM veicolo_noleggio v
             JOIN circuito c ON c.id = v.circuito_id
             WHERE v.id = :id AND v.azienda_id = :a",
            [':id' => $id, ':a' => $aziendaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    public static function loadByCircuitoAndAzienda(int $circuitoId, int $aziendaId, ?string $categoria = null): array
    {
        $sql = "SELECT v.*, c.nome_circuito,
                       (SELECT COUNT(*) FROM prenotazione p WHERE p.veicolo_noleggio_id = v.id) AS pren_count
                FROM veicolo_noleggio v
                JOIN circuito c ON c.id = v.circuito_id
                WHERE v.circuito_id = :c AND v.azienda_id = :a";
        $args = [':c' => $circuitoId, ':a' => $aziendaId];
        if ($categoria !== null && in_array($categoria, ['auto', 'moto'], true)) {
            $sql .= ' AND v.categoria = :cat';
            $args[':cat'] = $categoria;
        }
        $sql .= ' ORDER BY v.marca, v.modello, v.targa';

        return FDataBase::executeQuery($sql, $args)->fetchAllAssociative();
    }

    public static function loadCircuitiByAzienda(int $aziendaId): array
    {
        return FDataBase::executeQuery(
            'SELECT c.id, c.nome_circuito, c.localita,
                    COUNT(v.id) AS veicoli_count,
                    SUM(CASE WHEN v.disponibile = 1 THEN 1 ELSE 0 END) AS veicoli_disponibili
             FROM veicolo_noleggio v
             JOIN circuito c ON c.id = v.circuito_id
             WHERE v.azienda_id = :a
             GROUP BY c.id, c.nome_circuito, c.localita
             ORDER BY c.nome_circuito',
            [':a' => $aziendaId]
        )->fetchAllAssociative();
    }

    public static function targaInUso(string $targa, int $escludiId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM veicolo_noleggio WHERE targa = :t';
        $args = [':t' => strtoupper($targa)];
        if ($escludiId > 0) {
            $sql .= ' AND id <> :id';
            $args[':id'] = $escludiId;
        }

        return (int) FDataBase::executeQuery($sql, $args)->fetchOne() > 0;
    }

    /**
     * Prezzo dal form: l'importo arriva nella valuta scelta dall'azienda e
     * viene sempre salvato in EUR (tassi di FCambioValuta).
     */
    private static function prezzoEuroDaForm(array $dati): float
    {
        $importo = (float) str_replace(',', '.', (string) ($dati['prezzo_giorno'] ?? '0'));
        $valuta  = (string) ($dati['prezzo_valuta'] ?? 'EUR');

        return FCambioValuta::inEuro($importo, $valuta);
    }

    /**
     * Crea un veicolo dai dati del form, legato al circuito scelto e
     * all'azienda loggata.
     */
    public static function creaDaForm(int $aziendaId, array $dati): array
    {
        $potenzaRaw = trim((string) ($dati['potenza_cv'] ?? ''));
        $annoRaw    = trim((string) ($dati['anno'] ?? ''));
        $prezzo     = self::prezzoEuroDaForm($dati);

        return FPersistentManager::transaction(static function () use (
            $aziendaId,
            $dati,
            $potenzaRaw,
            $annoRaw,
            $prezzo
        ): array {
            $veicolo = new EVeicoloNoleggio(
                $aziendaId,
                (int) ($dati['circuito_id'] ?? 0),
                targa_normalizza((string) ($dati['targa'] ?? '')),
                (string) ($dati['categoria'] ?? 'auto'),
                (int) ($dati['capienza'] ?? 1),
                $potenzaRaw !== '' ? (int) $potenzaRaw : null,
                $annoRaw !== '' ? (int) $annoRaw : null,
                trim((string) ($dati['marca'] ?? '')) ?: null,
                trim((string) ($dati['modello'] ?? '')) ?: null,
                $prezzo,
                (string) ($dati['disponibile'] ?? '1') === '1'
            );
            $veicoloId = self::store($veicolo);

            $creato = self::loadByIdAndAzienda($veicoloId, $aziendaId);
            if (!$creato) {
                throw new RuntimeException('Impossibile caricare il veicolo creato.');
            }

            return $creato;
        });
    }

    /** Aggiorna un veicolo dell'azienda dai dati del form. */
    public static function aggiornaDaForm(int $veicoloId, int $aziendaId, array $dati): array
    {
        $potenzaRaw = trim((string) ($dati['potenza_cv'] ?? ''));
        $annoRaw    = trim((string) ($dati['anno'] ?? ''));
        $prezzo     = self::prezzoEuroDaForm($dati);

        return FPersistentManager::transaction(static function () use (
            $veicoloId,
            $aziendaId,
            $dati,
            $potenzaRaw,
            $annoRaw,
            $prezzo
        ): array {
            $veicolo = FPersistentManager::find(EVeicoloNoleggio::class, $veicoloId);
            if (!$veicolo instanceof EVeicoloNoleggio || $veicolo->getAziendaId() !== $aziendaId) {
                throw new RuntimeException('Veicolo non trovato.');
            }

            $veicolo->setTarga(targa_normalizza((string) ($dati['targa'] ?? '')));
            $veicolo->setCircuitoId((int) ($dati['circuito_id'] ?? $veicolo->getCircuitoId()));
            $veicolo->setCategoria((string) ($dati['categoria'] ?? 'auto'));
            $veicolo->setCapienza((int) ($dati['capienza'] ?? 1));
            $veicolo->setPotenzaCv($potenzaRaw !== '' ? (int) $potenzaRaw : null);
            $veicolo->setAnno($annoRaw !== '' ? (int) $annoRaw : null);
            $veicolo->setMarca(trim((string) ($dati['marca'] ?? '')) ?: null);
            $veicolo->setModello(trim((string) ($dati['modello'] ?? '')) ?: null);
            $veicolo->setPrezzo($prezzo);
            $veicolo->setDisponibile((string) ($dati['disponibile'] ?? '1') === '1');
            FPersistentManager::flush();

            $aggiornato = self::loadByIdAndAzienda($veicoloId, $aziendaId);
            if (!$aggiornato) {
                throw new RuntimeException('Impossibile caricare il veicolo aggiornato.');
            }

            return $aggiornato;
        });
    }

    public static function deleteByIdAndAzienda(int $id, int $aziendaId): void
    {
        $veicolo = FPersistentManager::find(EVeicoloNoleggio::class, $id);
        if ($veicolo instanceof EVeicoloNoleggio && $veicolo->getAziendaId() === $aziendaId) {
            FPersistentManager::remove($veicolo);
        }
    }

    public static function countByAzienda(int $aziendaId): int
    {
        return FPersistentManager::countWhere(EVeicoloNoleggio::class, 'e.aziendaId = :u', ['u' => $aziendaId]);
    }

    public static function countDisponibiliByAzienda(int $aziendaId): int
    {
        return FPersistentManager::countWhere(
            EVeicoloNoleggio::class,
            'e.aziendaId = :u AND e.disponibile = true',
            ['u' => $aziendaId]
        );
    }
}
