<?php

// operazioni sulle richieste di affiliazione gestori e aziende noleggio
class FAffiliazione
{
    public const STATO_IN_ATTESA  = 'in_attesa';
    public const STATO_APPROVATA  = 'approvata';
    public const STATO_RIFIUTATA  = 'rifiutata';

    public static function loadPendingRichieste(): array
    {
        $out = [];

        foreach (FGestoreCircuiti::loadAllJoinUtente() as $row) {
            if (($row['affiliazione'] ?? '') === self::STATO_IN_ATTESA) {
                $out[] = self::arricchisciRiga($row, EGestoreCircuiti::$ruolo, 'Gestore circuiti');
            }
        }

        foreach (FGestoreNoleggio::loadAllJoinUtente() as $row) {
            if (($row['affiliazione'] ?? '') === self::STATO_IN_ATTESA) {
                $out[] = self::arricchisciRiga($row, EGestoreNoleggio::$ruolo, 'Azienda noleggio');
            }
        }

        foreach (self::loadPilotiByDocumenti([EPilota::DOC_IN_ATTESA]) as $row) {
            // documenti incompleti non vanno inviati alla convalida admin
            if (!self::pilotaDocumentiCompleti($row)) {
                continue;
            }
            $out[] = $row;
        }

        self::ordinaPerNome($out);

        return $out;
    }

    // true se il pilota ha caricato entrambi i documenti richiesti
    public static function pilotaDocumentiCompleti(array $row): bool
    {
        return trim((string) ($row['certificato_medico_path'] ?? '')) !== ''
            && trim((string) ($row['licenza_path'] ?? '')) !== '';
    }

    // storico delle richieste già elaborate, approvate o respinte
    public static function loadStoricoRichieste(): array
    {
        $out = [];
        $elaborate = [self::STATO_APPROVATA, self::STATO_RIFIUTATA];

        foreach (FGestoreCircuiti::loadAllJoinUtente() as $row) {
            if (in_array($row['affiliazione'] ?? '', $elaborate, true)) {
                $out[] = self::arricchisciRiga($row, EGestoreCircuiti::$ruolo, 'Gestore circuiti');
            }
        }

        foreach (FGestoreNoleggio::loadAllJoinUtente() as $row) {
            if (in_array($row['affiliazione'] ?? '', $elaborate, true)) {
                $out[] = self::arricchisciRiga($row, EGestoreNoleggio::$ruolo, 'Azienda noleggio');
            }
        }

        // per i piloti lo storico mostra i documenti già approvati o respinti
        foreach (self::loadPilotiByDocumenti([EPilota::DOC_APPROVATI, EPilota::DOC_RESPINTI]) as $row) {
            $out[] = $row;
        }

        self::ordinaPerNome($out);

        return $out;
    }

    // carica i piloti con documenti_stato tra quelli indicati, arricchiti come richiesta
    private static function loadPilotiByDocumenti(array $statiDocumenti): array
    {
        if ($statiDocumenti === []) {
            return [];
        }

        $place = [];
        $args  = [];
        foreach (array_values($statiDocumenti) as $i => $st) {
            $key        = ':s' . $i;
            $place[]    = $key;
            $args[$key] = $st;
        }

        $rows = FDataBase::executeQuery(
            "SELECT u.id, u.nome, u.cognome, u.email, u.stato_account, u.data_creazione,
                    p.categoria, p.licenza, p.scadenza_licenza,
                    p.certificato_medico_path, p.licenza_path, p.documenti_stato
             FROM pilota p
             JOIN account u ON u.id = p.id
             WHERE p.documenti_stato IN (" . implode(', ', $place) . ')',
            $args
        )->fetchAllAssociative();

        return array_map(self::arricchisciPilota(...), $rows);
    }

    // mappa una riga pilota nel formato uniforme delle richieste
    private static function arricchisciPilota(array $row): array
    {
        $row['tipo']         = EPilota::$ruolo;
        $row['tipo_label']   = 'Pilota';
        $row['nome_societa'] = '';
        $row['partita_iva']  = '';
        $row['affiliazione'] = match ((string) ($row['documenti_stato'] ?? '')) {
            EPilota::DOC_IN_ATTESA => self::STATO_IN_ATTESA,
            EPilota::DOC_APPROVATI => self::STATO_APPROVATA,
            EPilota::DOC_RESPINTI  => self::STATO_RIFIUTATA,
            default                => (string) ($row['documenti_stato'] ?? ''),
        };

        return $row;
    }

    private static function ordinaPerNome(array &$righe): void
    {
        usort($righe, static fn (array $a, array $b): int =>
            strcmp(
                (string) ($a['cognome'] ?? '') . (string) ($a['nome'] ?? ''),
                (string) ($b['cognome'] ?? '') . (string) ($b['nome'] ?? '')
            ));
    }

    public static function loadRichiestaById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $gestore = self::loadGestoreDettaglio($id);
        if ($gestore !== null) {
            return $gestore;
        }

        $noleggio = self::loadNoleggioDettaglio($id);
        if ($noleggio !== null) {
            return $noleggio;
        }

        return self::loadPilotaDettaglio($id);
    }

    private static function loadPilotaDettaglio(int $id): ?array
    {
        $rows = FDataBase::executeQuery(
            "SELECT u.id, u.nome, u.cognome, u.email, u.stato_account, u.data_creazione,
                    p.categoria, p.licenza, p.scadenza_licenza,
                    p.certificato_medico_path, p.licenza_path, p.documenti_stato
             FROM pilota p
             JOIN account u ON u.id = p.id
             WHERE p.id = :id",
            [':id' => $id]
        )->fetchAllAssociative();

        if ($rows === []) {
            return null;
        }

        return self::arricchisciPilota($rows[0]);
    }

    public static function approvaSeInAttesa(int $id, string $tipo): array
    {
        return self::aggiornaStatoSeInAttesa($id, $tipo, self::STATO_APPROVATA);
    }

    public static function respingiSeInAttesa(int $id, string $tipo): array
    {
        return self::aggiornaStatoSeInAttesa($id, $tipo, self::STATO_RIFIUTATA);
    }

    private static function aggiornaStatoSeInAttesa(int $id, string $tipo, string $nuovoStato): array
    {
        $richiesta = self::loadRichiestaById($id);

        if ($richiesta === null) {
            return ['ok' => false, 'error' => 'Richiesta di affiliazione non trovata.', 'richiesta' => null];
        }

        if ((string) ($richiesta['tipo'] ?? '') !== $tipo) {
            return ['ok' => false, 'error' => 'Tipo di richiesta non valido.', 'richiesta' => $richiesta];
        }

        if ((string) ($richiesta['affiliazione'] ?? '') !== self::STATO_IN_ATTESA) {
            return [
                'ok'        => false,
                'error'     => 'La richiesta è già stata elaborata (stato attuale: '
                    . (string) ($richiesta['affiliazione'] ?? '') . ').',
                'richiesta' => $richiesta,
            ];
        }

        if ($tipo === EGestoreCircuiti::$ruolo) {
            FGestoreCircuiti::updateAffiliazione($id, $nuovoStato);
        } elseif ($tipo === EGestoreNoleggio::$ruolo) {
            FGestoreNoleggio::updateAffiliazione($id, $nuovoStato);
        } elseif ($tipo === EPilota::$ruolo) {
            // per i piloti la convalida riguarda i documenti, l'account resta attivo
            $docStato = $nuovoStato === self::STATO_APPROVATA
                ? EPilota::DOC_APPROVATI
                : EPilota::DOC_RESPINTI;
            FPilota::updateDocumentiStato($id, $docStato);
        } else {
            return ['ok' => false, 'error' => 'Tipo di richiesta non supportato.', 'richiesta' => $richiesta];
        }

        return ['ok' => true, 'error' => null, 'richiesta' => self::loadRichiestaById($id)];
    }

    private static function arricchisciRiga(array $row, string $tipo, string $tipoLabel): array
    {
        $row['tipo']       = $tipo;
        $row['tipo_label'] = $tipoLabel;

        return $row;
    }

    private static function loadGestoreDettaglio(int $id): ?array
    {
        $rows = FDataBase::executeQuery(
            "SELECT u.id, u.nome, u.cognome, u.email, u.stato_account, u.data_creazione,
                    g.nome_societa, g.partita_iva, g.affiliazione
             FROM gestore_circuiti g
             JOIN account u ON u.id = g.id
             WHERE g.id = :id",
            [':id' => $id]
        )->fetchAllAssociative();

        if ($rows === []) {
            return null;
        }

        return self::arricchisciRiga($rows[0], EGestoreCircuiti::$ruolo, 'Gestore circuiti');
    }

    private static function loadNoleggioDettaglio(int $id): ?array
    {
        $rows = FDataBase::executeQuery(
            "SELECT u.id, u.nome, u.cognome, u.email, u.stato_account, u.data_creazione,
                    a.nome_societa, a.partita_iva, a.affiliazione
             FROM azienda_noleggio a
             JOIN account u ON u.id = a.id
             WHERE a.id = :id",
            [':id' => $id]
        )->fetchAllAssociative();

        if ($rows === []) {
            return null;
        }

        return self::arricchisciRiga($rows[0], EGestoreNoleggio::$ruolo, 'Azienda noleggio');
    }
}
