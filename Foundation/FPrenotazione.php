<?php

// repository prenotazioni
class FPrenotazione extends FRepository
{
    protected static function entityClass(): string
    {
        return EPrenotazione::class;
    }

    public static function loadDettaglio(int $id, int $pilotaId): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT p.*, s.inizio AS inizio_sessione, s.fine AS fine_sessione,
                    s.circuito_id, c.nome_circuito,
                    pr.titolo AS promozione_titolo,
                    pr.descrizione AS promozione_descrizione,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             LEFT JOIN promozione pr ON pr.id = p.promozione_id
             LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE p.id = :id AND p.pilota_id = :u',
            [':id' => $id, ':u' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    public static function loadDettaglioByCodice(string $codice, int $pilotaId): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT p.*, s.inizio AS inizio_sessione, s.fine AS fine_sessione,
                    s.circuito_id, c.nome_circuito,
                    pr.titolo AS promozione_titolo,
                    pr.descrizione AS promozione_descrizione,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             LEFT JOIN promozione pr ON pr.id = p.promozione_id
             LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE p.codice_identificativo = :cod AND p.pilota_id = :u',
            [':cod' => $codice, ':u' => $pilotaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    public static function loadUltimePerFatturazione(int $limit = 20): array
    {
        return FDataBase::executeQuery(
            'SELECT p.codice_identificativo, p.prezzo_importo, p.prezzo_valuta, p.data_inserimento,
                    u.nome, u.cognome, c.nome_circuito
             FROM prenotazione p
             JOIN account u ON u.id = p.pilota_id
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             ORDER BY p.data_inserimento DESC, p.id DESC
             LIMIT ' . (int) $limit
        )->fetchAllAssociative();
    }

    public static function loadByGestoreForFatturazione(int $gestoreId, string $q = ''): array
    {
        $where = ['c.gestore_id = :g'];
        $args  = [':g' => $gestoreId, ':g2' => $gestoreId];

        if ($q !== '') {
            // CONCAT trova anche la ricerca su nome e cognome completi
            $where[] = "(c.nome_circuito LIKE :q1 OR p.codice_identificativo LIKE :q2
                         OR u.nome LIKE :q3 OR u.cognome LIKE :q4
                         OR CONCAT(u.nome, ' ', u.cognome) LIKE :q5)";
            $args[':q1'] = "%{$q}%";
            $args[':q2'] = "%{$q}%";
            $args[':q3'] = "%{$q}%";
            $args[':q4'] = "%{$q}%";
            $args[':q5'] = "%{$q}%";
        }

        // fattura emessa da questo gestore, che vede solo la sua quota di accesso
        return FDataBase::executeQuery(
            "SELECT p.id, p.codice_identificativo, p.imponibile_accesso, p.prezzo_valuta,
                    s.inizio AS inizio_sessione, s.fine AS fine_sessione, p.stato,
                    p.pilota_id, c.nome_circuito, u.nome, u.cognome,
                    df.id AS fattura_id, df.numero_formattato AS fattura_numero
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             JOIN account u ON u.id = p.pilota_id
             LEFT JOIN documento_fiscale df
                    ON df.prenotazione_id = p.id
                   AND df.tipo = 'fattura'
                   AND df.emittente_tipo = 'gestore_circuiti'
                   AND df.emittente_id = :g2
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.data_inserimento DESC, p.id DESC',
            $args
        )->fetchAllAssociative();
    }

    public static function loadByAziendaForFatturazione(int $aziendaId, string $q = ''): array
    {
        $where = ['v.azienda_id = :a', 'p.veicolo_noleggio_id IS NOT NULL'];
        $args  = [':a' => $aziendaId, ':a2' => $aziendaId];

        if ($q !== '') {
            // CONCAT trova anche la ricerca su nome e cognome completi
            $where[] = "(p.codice_identificativo LIKE :q1 OR v.marca LIKE :q2
                         OR v.modello LIKE :q3 OR v.targa LIKE :q4
                         OR u.nome LIKE :q5 OR u.cognome LIKE :q6
                         OR CONCAT(u.nome, ' ', u.cognome) LIKE :q7)";
            $args[':q1'] = "%{$q}%";
            $args[':q2'] = "%{$q}%";
            $args[':q3'] = "%{$q}%";
            $args[':q4'] = "%{$q}%";
            $args[':q5'] = "%{$q}%";
            $args[':q6'] = "%{$q}%";
            $args[':q7'] = "%{$q}%";
        }

        // fattura emessa da questa azienda, che vede solo la sua quota di noleggio
        return FDataBase::executeQuery(
            "SELECT p.id, p.codice_identificativo, p.imponibile_noleggio, p.prezzo_valuta,
                    s.inizio AS inizio_sessione, s.fine AS fine_sessione, p.stato,
                    p.pilota_id, u.nome, u.cognome,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa,
                    df.id AS fattura_id, df.numero_formattato AS fattura_numero
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             JOIN account u ON u.id = p.pilota_id
             LEFT JOIN documento_fiscale df
                    ON df.prenotazione_id = p.id
                   AND df.tipo = 'fattura'
                   AND df.emittente_tipo = 'azienda_noleggio'
                   AND df.emittente_id = :a2
             WHERE " . implode(' AND ', $where) . '
             ORDER BY p.data_inserimento DESC, p.id DESC',
            $args
        )->fetchAllAssociative();
    }

    public static function loadDettaglioForGestore(int $id, int $gestoreId): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT p.*, s.inizio AS inizio_sessione, s.fine AS fine_sessione,
                    s.circuito_id, c.nome_circuito,
                    u.nome AS pilota_nome, u.cognome AS pilota_cognome,
                    pr.titolo AS promozione_titolo, pr.descrizione AS promozione_descrizione
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             JOIN account u ON u.id = p.pilota_id
             LEFT JOIN promozione pr ON pr.id = p.promozione_id
             WHERE p.id = :id AND c.gestore_id = :g',
            [':id' => $id, ':g' => $gestoreId]
        )->fetchAssociative();

        return $r ?: null;
    }

    public static function loadDettaglioForAzienda(int $id, int $aziendaId): ?array
    {
        $r = FDataBase::executeQuery(
            'SELECT p.id, p.codice_identificativo, p.imponibile_noleggio, p.prezzo_valuta,
                    s.inizio AS inizio_sessione, s.fine AS fine_sessione, p.stato, p.data_inserimento,
                    p.sconto_importo, p.rimborso_previsto, p.causa_cancellazione,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa,
                    v.categoria AS v_categoria, v.capienza AS v_capienza,
                    v.potenza_cv AS v_potenza_cv, v.anno AS v_anno
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE p.id = :id AND v.azienda_id = :a',
            [':id' => $id, ':a' => $aziendaId]
        )->fetchAssociative();

        return $r ?: null;
    }

    // $filtro vale 'attive' oppure 'storico'
    public static function loadByPilotaFiltro(int $pilotaId, string $filtro, string $q = ''): array
    {
        $where = ['p.pilota_id = :u'];
        $args  = [':u' => $pilotaId];

        if ($filtro === 'storico') {
            $where[] = "(p.stato = 'cancellata' OR (p.stato = 'confermata' AND s.fine < NOW()))";
        } else {
            $where[] = "p.stato = 'confermata' AND s.fine >= NOW()";
        }
        if ($q !== '') {
            $where[] = '(c.nome_circuito LIKE :q1 OR p.codice_identificativo LIKE :q2)';
            $args[':q1'] = "%{$q}%";
            $args[':q2'] = "%{$q}%";
        }

        $sql = "SELECT p.*, s.inizio AS inizio_sessione, s.fine AS fine_sessione,
                       s.circuito_id, c.nome_circuito
                FROM prenotazione p
                JOIN sessione s ON s.id = p.sessione_id
                JOIN circuito c ON c.id = s.circuito_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY s.inizio DESC, p.id DESC';
        return FDataBase::executeQuery($sql, $args)->fetchAllAssociative();
    }

    public static function loadProssimeByPilota(int $pilotaId, int $limit = 5): array
    {
        return FDataBase::executeQuery(
            "SELECT p.id, p.stato, p.codice_identificativo, s.inizio AS inizio_sessione,
                    p.prezzo_importo, p.prezzo_valuta, p.veicolo_noleggio_id,
                    c.nome_circuito, c.localita
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             WHERE p.pilota_id = :u AND s.fine >= NOW()
               AND p.stato = 'confermata'
             ORDER BY s.inizio
             LIMIT " . (int) $limit,
            [':u' => $pilotaId]
        )->fetchAllAssociative();
    }

    // prossimi noleggi di un'azienda, prenotazioni con veicolo
    public static function loadProssimiNoleggiByAzienda(int $aziendaId, int $limit = 6): array
    {
        return FDataBase::executeQuery(
            "SELECT p.id, p.codice_identificativo, s.inizio AS inizio_sessione,
                    p.imponibile_noleggio, p.prezzo_valuta, p.stato,
                    c.nome_circuito,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             JOIN circuito c ON c.id = s.circuito_id
             WHERE v.azienda_id = :a AND s.fine >= NOW()
               AND p.stato = 'confermata'
             ORDER BY s.inizio
             LIMIT " . (int) $limit,
            [':a' => $aziendaId]
        )->fetchAllAssociative();
    }

    // andamento mensile per i grafici, i mesi a zero li aggiunge mesiSkeleton

    // inizio del mese più vecchio della finestra di $mesi mesi, in DATETIME SQL
    private static function inizioFinestraMesi(int $mesi): string
    {
        $mesi = max(1, $mesi);
        return (new DateTimeImmutable('first day of this month 00:00:00'))
            ->modify('-' . ($mesi - 1) . ' months')
            ->format('Y-m-d H:i:s');
    }

    // mappa ordinata 'YYYY-MM' con riga a zero per gli ultimi $mesi mesi
    private static function mesiSkeleton(int $mesi): array
    {
        $mesi = max(1, $mesi);
        $cursore = (new DateTimeImmutable('first day of this month'))
            ->modify('-' . ($mesi - 1) . ' months');

        $out = [];
        for ($i = 0; $i < $mesi; $i++) {
            $ym = $cursore->format('Y-m');
            $out[$ym] = ['ym' => $ym, 'prenotazioni' => 0, 'ricavi' => 0.0];
            $cursore = $cursore->modify('+1 month');
        }

        return $out;
    }

    // riversa le righe aggregate nello scheletro mensile
    private static function fondiMesi(array $skeleton, array $rows): array
    {
        foreach ($rows as $row) {
            $ym = (string) ($row['ym'] ?? '');
            if (isset($skeleton[$ym])) {
                $skeleton[$ym]['prenotazioni'] = (int) ($row['prenotazioni'] ?? 0);
                $skeleton[$ym]['ricavi']       = (float) ($row['ricavi'] ?? 0);
            }
        }

        return array_values($skeleton);
    }

    // prenotazioni confermate e ricavi per mese sui circuiti di un gestore
    public static function andamentoMensileGestore(int $gestoreId, int $mesi = 6, ?int $circuitoId = null): array
    {
        $args = [':g' => $gestoreId, ':start' => self::inizioFinestraMesi($mesi)];
        $filtroCircuito = '';
        if ($circuitoId !== null && $circuitoId > 0) {
            $filtroCircuito = ' AND c.id = :c';
            $args[':c'] = $circuitoId;
        }

        $rows = FDataBase::executeQuery(
            "SELECT DATE_FORMAT(p.data_inserimento, '%Y-%m') AS ym,
                    COUNT(*) AS prenotazioni,
                    COALESCE(SUM(p.imponibile_accesso), 0) AS ricavi
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             WHERE c.gestore_id = :g
               AND p.stato = 'confermata'
               AND p.data_inserimento >= :start" . $filtroCircuito . "
             GROUP BY ym
             ORDER BY ym",
            $args
        )->fetchAllAssociative();

        return self::fondiMesi(self::mesiSkeleton($mesi), $rows);
    }

    // noleggi confermati e ricavi per mese di un'azienda
    public static function andamentoMensileAzienda(int $aziendaId, int $mesi = 6): array
    {
        $rows = FDataBase::executeQuery(
            "SELECT DATE_FORMAT(p.data_inserimento, '%Y-%m') AS ym,
                    COUNT(*) AS prenotazioni,
                    COALESCE(SUM(p.imponibile_noleggio), 0) AS ricavi
             FROM prenotazione p
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE v.azienda_id = :a
               AND p.stato = 'confermata'
               AND p.data_inserimento >= :start
             GROUP BY ym
             ORDER BY ym",
            [':a' => $aziendaId, ':start' => self::inizioFinestraMesi($mesi)]
        )->fetchAllAssociative();

        return self::fondiMesi(self::mesiSkeleton($mesi), $rows);
    }

    // assicurazioni stipulate per mese, solo il conteggio, i ricavi li calcola il chiamante
    public static function andamentoMensileAssicurazioni(int $mesi = 6): array
    {
        $rows = FDataBase::executeQuery(
            "SELECT DATE_FORMAT(data_inserimento, '%Y-%m') AS ym,
                    COUNT(*) AS prenotazioni,
                    0 AS ricavi
             FROM prenotazione
             WHERE stato = 'confermata'
               AND assicurazione = 1
               AND data_inserimento >= :start
             GROUP BY ym
             ORDER BY ym",
            [':start' => self::inizioFinestraMesi($mesi)]
        )->fetchAllAssociative();

        return self::fondiMesi(self::mesiSkeleton($mesi), $rows);
    }

    // ricavi totali delle prenotazioni confermate su tutta la piattaforma
    public static function sumRicaviConfermate(): float
    {
        return (float) FDataBase::executeQuery(
            "SELECT COALESCE(SUM(prezzo_importo), 0)
             FROM prenotazione WHERE stato = 'confermata'"
        )->fetchOne();
    }

    public static function loadAttiveByPilotaIntervallo(
        int $pilotaId,
        int $circuitoId,
        string $inizio,
        string $fine
    ): array {
        return FDataBase::executeQuery(
            "SELECT p.id, p.sessione_id, s.inizio AS inizio_sessione,
                    s.fine AS fine_sessione, p.stato
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             WHERE p.pilota_id = :p
               AND s.circuito_id = :c
               AND p.stato = 'confermata'
               AND s.fine >= NOW()
               AND s.inizio < :fine AND s.fine > :inizio
             ORDER BY s.inizio ASC",
            [
                ':p'      => $pilotaId,
                ':c'      => $circuitoId,
                ':inizio' => $inizio,
                ':fine'   => $fine,
            ]
        )->fetchAllAssociative();
    }

    // assegna il box con meno piloti nella sessione, lancia eccezione se sono tutti pieni
    public static function assegnaBox(
        int $sessioneId,
        int $numeroBoxCircuito,
        int $postiPerBox
    ): int {
        if ($numeroBoxCircuito < 1) {
            throw new RuntimeException('Il circuito non ha box configurati.');
        }

        $counts = self::countPrenotazioniPerBox($sessioneId);

        for ($box = 1; $box <= $numeroBoxCircuito; $box++) {
            $occupati = $counts[$box] ?? 0;
            if ($occupati < $postiPerBox) {
                return $box;
            }
        }

        throw new RuntimeException('Nessun box disponibile per questa sessione.');
    }

    // mappa numero_box con il conteggio prenotazioni attive della sessione
    private static function countPrenotazioniPerBox(int $sessioneId): array
    {
        $rows = FDataBase::executeQuery(
            "SELECT numero_box, COUNT(*) AS totale
             FROM prenotazione
             WHERE sessione_id = :s
               AND stato = 'confermata'
               AND numero_box IS NOT NULL
             GROUP BY numero_box",
            [':s' => $sessioneId]
        )->fetchAllAssociative();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['numero_box']] = (int) $row['totale'];
        }

        return $out;
    }

    // prenotazioni confermate di una sessione
    public static function loadConfermateBySessione(int $sessioneId): array
    {
        return FDataBase::executeQuery(
            "SELECT p.id, p.codice_identificativo, p.prezzo_importo, p.prezzo_valuta,
                    p.numero_box,
                    u.nome AS pilota_nome, u.cognome AS pilota_cognome
             FROM prenotazione p
             JOIN account u ON u.id = p.pilota_id
             WHERE p.sessione_id = :s
               AND p.stato = 'confermata'
             ORDER BY u.cognome ASC, u.nome ASC",
            [':s' => $sessioneId]
        )->fetchAllAssociative();
    }

    // tutte le prenotazioni di una sessione con pilota ed eventuale veicolo
    public static function loadBySessione(int $sessioneId): array
    {
        return FDataBase::executeQuery(
            "SELECT p.id, p.pilota_id, p.codice_identificativo, p.imponibile_accesso, p.prezzo_valuta,
                    p.numero_box, p.targa_veicolo, p.veicolo_noleggio_id, p.assicurazione,
                    p.stato, s.inizio AS inizio_sessione, s.fine AS fine_sessione, p.data_inserimento,
                    u.nome AS pilota_nome, u.cognome AS pilota_cognome, u.email AS pilota_email,
                    v.marca AS v_marca, v.modello AS v_modello, v.targa AS v_targa
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN account u ON u.id = p.pilota_id
             LEFT JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE p.sessione_id = :s
             ORDER BY (p.stato = 'confermata') DESC, p.numero_box ASC, u.cognome ASC, u.nome ASC",
            [':s' => $sessioneId]
        )->fetchAllAssociative();
    }

    // $dati ha chiavi targa_veicolo e assicurazione, ritorna il dettaglio aggiornato
    public static function aggiornaPrenotazione(int $id, int $pilotaId, array $dati): array
    {
        $dettaglio = self::loadDettaglio($id, $pilotaId);
        if (!$dettaglio) {
            throw new InvalidArgumentException('Prenotazione non trovata.');
        }
        if (($dettaglio['stato'] ?? '') !== 'confermata') {
            throw new InvalidArgumentException('Solo le prenotazioni confermate possono essere modificate.');
        }
        if (strtotime((string) ($dettaglio['fine_sessione'] ?? '')) < time()) {
            throw new InvalidArgumentException('La prenotazione è già conclusa e non può essere modificata.');
        }

        $pren = FPersistentManager::find(EPrenotazione::class, $id);
        if (!$pren instanceof EPrenotazione || $pren->getPilotaId() !== $pilotaId) {
            throw new InvalidArgumentException('Non sei autorizzato a modificare questa prenotazione.');
        }

        $prezzoAssic = FConfigurazionePiattaforma::prezzoAssicurazione();
        $basePrezzo  = $pren->isAssicurazione()
            ? $pren->getPrezzoImporto() - $prezzoAssic
            : $pren->getPrezzoImporto();

        if (empty($dettaglio['veicolo_noleggio_id'])) {
            $targa = targa_normalizza((string) ($dati['targa_veicolo'] ?? ''));
            if ($targa === '' || !targa_valida($targa)) {
                throw new InvalidArgumentException('Targa non valida: usa il formato italiano (auto AA000AA o moto AA00000).');
            }
            $pren->setTargaVeicolo($targa);
        }

        $nuovaAssic = isset($dati['assicurazione'])
            ? (bool) filter_var($dati['assicurazione'], FILTER_VALIDATE_BOOLEAN)
            : $pren->isAssicurazione();
        $pren->setAssicurazione($nuovaAssic);
        $pren->setPrezzoImporto(round(max(0.0, $basePrezzo + ($nuovaAssic ? $prezzoAssic : 0.0)), 2));

        FPersistentManager::flush();

        $aggiornato = self::loadDettaglio($id, $pilotaId);
        if (!$aggiornato) {
            throw new RuntimeException('Impossibile caricare la prenotazione aggiornata.');
        }

        return $aggiornato;
    }

    public static function cancella(int $id, int $pilotaId, string $causa, float $rimborso): void
    {
        $dettaglio = self::loadDettaglio($id, $pilotaId);
        if (!$dettaglio) {
            throw new InvalidArgumentException('Prenotazione non trovata.');
        }
        if (($dettaglio['stato'] ?? '') !== 'confermata') {
            throw new InvalidArgumentException('Solo le prenotazioni confermate possono essere annullate.');
        }
        if (strtotime((string) ($dettaglio['fine_sessione'] ?? '')) < time()) {
            throw new InvalidArgumentException('La prenotazione è già conclusa e non può essere annullata.');
        }

        $pren = FPersistentManager::find(EPrenotazione::class, $id);
        if (!$pren instanceof EPrenotazione || $pren->getPilotaId() !== $pilotaId) {
            throw new InvalidArgumentException('Non sei autorizzato ad annullare questa prenotazione.');
        }

        $causaNorm = trim($causa);
        if ($causaNorm === '') {
            throw new InvalidArgumentException('Indica una motivazione per la cancellazione.');
        }
        if (mb_strlen($causaNorm) > 255) {
            throw new InvalidArgumentException('La motivazione non può superare 255 caratteri.');
        }

        $pren->setStato('cancellata');
        $pren->setCausaCancellazione($causaNorm);
        $pren->setRimborsoPrevisto($rimborso);
        FPersistentManager::flush();

        // note di credito proporzionali al rimborso, best effort
        try {
            FFatturazione::emettiNoteCreditoPerPrenotazione(
                $id,
                $rimborso,
                $pren->getPrezzoImporto(),
                $causaNorm
            );
        } catch (Throwable $e) {
            error_log('Emissione note di credito fallita: ' . $e->getMessage());
        }
    }

    // quota accesso pista incassata dal gestore nel mese corrente
    public static function sumGuadagnoMeseGestore(int $gestoreId): float
    {
        return (float) FDataBase::executeQuery(
            "SELECT COALESCE(SUM(p.imponibile_accesso), 0)
             FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             WHERE c.gestore_id = :u AND p.stato = 'confermata'
               AND p.data_inserimento >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')",
            [':u' => $gestoreId]
        )->fetchOne();
    }

    public static function countByAziendaAttive(int $aziendaId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione p
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE v.azienda_id = :u AND p.stato = 'confermata'",
            [':u' => $aziendaId]
        )->fetchOne();
    }

    public static function sumGuadagnoAzienda(int $aziendaId): float
    {
        return (float) FDataBase::executeQuery(
            "SELECT COALESCE(SUM(p.imponibile_noleggio), 0)
             FROM prenotazione p
             JOIN veicolo_noleggio v ON v.id = p.veicolo_noleggio_id
             WHERE v.azienda_id = :u AND p.stato = 'confermata'",
            [':u' => $aziendaId]
        )->fetchOne();
    }

    public static function countFutureByGestore(int $gestoreId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             JOIN circuito c ON c.id = s.circuito_id
             WHERE c.gestore_id = :g AND s.fine >= NOW()
               AND p.stato = 'confermata'",
            [':g' => $gestoreId]
        )->fetchOne();
    }

    public static function countStoricheByPilotaCircuito(int $pilotaId, int $circuitoId): int
    {
        return (int) FDataBase::executeQuery(
            "SELECT COUNT(*) FROM prenotazione p
             JOIN sessione s ON s.id = p.sessione_id
             WHERE p.pilota_id = :p
               AND s.circuito_id = :c
               AND p.stato <> 'cancellata'
               AND s.fine < NOW()",
            [':p' => $pilotaId, ':c' => $circuitoId]
        )->fetchOne();
    }

    public static function countConAssicurazione(): int
    {
        return FPersistentManager::countWhere(EPrenotazione::class, 'e.assicurazione = true');
    }
}
