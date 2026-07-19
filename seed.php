<?php

declare(strict_types=1);

/**
 * TrackPortal - popolamento del database di prova.
 *
 * trackportal.sql crea SOLO il database vuoto; questo script lo popola.
 * Da eseguire DOPO aver importato lo schema:
 *   http://localhost/TrackPortal/seed.php      (oppure da CLI: php seed.php)
 */

if (!defined('TRACKPORTAL_BASE_DIR')) {
    define('TRACKPORTAL_BASE_DIR', __DIR__);
}

$autoload = TRACKPORTAL_BASE_DIR . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    die("Dipendenze mancanti. Esegui composer install in:\n" . TRACKPORTAL_BASE_DIR . "\n");
}
require_once $autoload;

// Configurazione condivisa con l'app: vive fuori dalla document root (vedi index.php).
$trackPortalConfig = __DIR__ . '/trackportal-config/config.php';
if (!is_file($trackPortalConfig)) {
    http_response_code(500);
    die("Configurazione mancante. Verifica il file:\n" . $trackPortalConfig . "\n");
}
require_once $trackPortalConfig;

ini_set('display_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set(APP_TIMEZONE);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

// FASE 1 - DATI BASE (solo se il database e' vuoto)
$accountEsistenti = (int) FDataBase::executeQuery('SELECT COUNT(*) FROM account')->fetchOne();

if ($accountEsistenti > 0) {
    echo "Dati base gia presenti ({$accountEsistenti} account): inserimento saltato.\n\n";
} else {
    // Tutti gli account seed condividono la stessa password.
    $plain = 'ilsupremo';
    $hash  = password_hash($plain, PASSWORD_BCRYPT);

    // Account (id espliciti: sono referenziati dalle estensioni di ruolo).
    $accounts = [
        [1, 'Admin',  'Sistema',    'admin@trackportal.test',      'admin'],
        [2, 'Marco',  'Rossi',      'marco@trackportal.test',      'pilota'],
        [3, 'Luca',   'Bianchi',    'luca@trackportal.test',       'gestore_circuiti'],
        [4, 'Speed',  'Rent',       'rent@trackportal.test',       'gestore_noleggio'],
        // Piloti aggiuntivi (categorie miste): popolano le sessioni della Fase 3.
        [5, 'Giulia', 'Conti',      'giulia@trackportal.test',     'pilota'],
        [6, 'Andrea', 'Verdi',      'andrea@trackportal.test',     'pilota'],
        [7, 'Sara',   'Neri',       'sara@trackportal.test',       'pilota'],
        // Secondo gestore circuiti e seconda azienda di noleggio.
        [8, 'Verdi',  'Motorsport', 'motorsport@trackportal.test', 'gestore_circuiti'],
        [9, 'Track',  'Rent',       'trackrent@trackportal.test',  'gestore_noleggio'],
    ];
    // id account => nome/cognome: unica fonte dei nomi delle persone, riusata
    // ovunque (titolari carta, e quindi prenotazioni/fatture) per non duplicarli.
    $nomeAccount = [];
    foreach ($accounts as [$id, $nome, $cognome, $email, $ruolo]) {
        $nomeAccount[$id] = ['nome' => $nome, 'cognome' => $cognome];
        FDataBase::executeStatement(
            "INSERT INTO account (id, nome, cognome, email, password, ruolo, stato_account, email_verificata)
             VALUES (:id, :nome, :cognome, :email, :h, :ruolo, 'attivo', 1)",
            [':id' => $id, ':nome' => $nome, ':cognome' => $cognome, ':email' => $email, ':h' => $hash, ':ruolo' => $ruolo]
        );
    }

    // Estensioni 1:1 per ruolo.
    FDataBase::executeStatement('INSERT INTO amministratore (id) VALUES (1)');

    FDataBase::executeStatement(
        "INSERT INTO pilota (id, categoria, licenza, scadenza_licenza,
            certificato_medico_path, licenza_path, documenti_stato,
            codice_fiscale, indirizzo, cap, comune, provincia,
            fatt_indirizzo, fatt_cap, fatt_comune, fatt_provincia) VALUES
            (2, 'amatoriale', 'PPL-ABC123', DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
             '/uploads/piloti/2/certificato_medico_seed.pdf', '/uploads/piloti/2/licenza_seed.pdf', 'approvati',
             'RSSMRC85M01F205X', 'Via Roma 12',       '20121', 'Milano',  'MI',
             'Via Roma 12',       '20121', 'Milano',  'MI'),
            (5, 'professionista', 'ACI-PRO567', DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
             '/uploads/piloti/5/certificato_medico_seed.pdf', '/uploads/piloti/5/licenza_seed.pdf', 'approvati',
             'CNTGLI90A41H501Y', 'Via Appia Nuova 45', '00179', 'Roma',    'RM',
             'Via Appia Nuova 45', '00179', 'Roma',    'RM'),
            (6, 'amatoriale', 'PPL-DEF890', DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
             '/uploads/piloti/6/certificato_medico_seed.pdf', '/uploads/piloti/6/licenza_seed.pdf', 'approvati',
             'VRDNDR88T10A944Z', 'Via Indipendenza 8', '40121', 'Bologna', 'BO',
             'Via Indipendenza 8', '40121', 'Bologna', 'BO'),
            (7, 'professionista', 'ACI-PRO901', DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
             '/uploads/piloti/7/certificato_medico_seed.pdf', '/uploads/piloti/7/licenza_seed.pdf', 'approvati',
             'NRESRA92E45D612W', 'Viale dei Mille 23', '50132', 'Firenze', 'FI',
             'Viale dei Mille 23', '50132', 'Firenze', 'FI')"
    );

    // Carte di credito dei piloti (riferite dalle prenotazioni demo e di sessione).
    // Il titolare è SEMPRE l'intestatario dell'account del pilota: così i nomi su
    // prenotazioni e fatture coincidono con quelli registrati a sistema.
    $carte = [
        // [id carta, pilota_id, numero mascherato, scadenza]
        [1, 2, '**** **** **** 4242', '12/2030'],
        [2, 5, '**** **** **** 5151', '08/2029'],
        [3, 6, '**** **** **** 6262', '03/2028'],
        [4, 7, '**** **** **** 7373', '11/2031'],
    ];
    foreach ($carte as [$cid, $pilotaId, $masked, $scadenza]) {
        FDataBase::executeStatement(
            "INSERT INTO carta_credito (id, pilota_id, numero_masked, nome_titolare, cognome_titolare, data_scadenza)
             VALUES (:id, :pil, :num, :nome, :cognome, :scad)",
            [
                ':id'      => $cid,
                ':pil'     => $pilotaId,
                ':num'     => $masked,
                ':nome'    => $nomeAccount[$pilotaId]['nome'],
                ':cognome' => $nomeAccount[$pilotaId]['cognome'],
                ':scad'    => $scadenza,
            ]
        );
    }

    FDataBase::executeStatement(
        "INSERT INTO gestore_circuiti (id, nome_societa, partita_iva, codice_fiscale,
            indirizzo, cap, comune, provincia, affiliazione) VALUES
            (3, 'Bianchi Racing SRL',   'IT01234567890', '01234567890',
                'Via Vedano 5',     '20900', 'Monza',              'MB', 'approvata'),
            (8, 'Verdi Motorsport SRL', 'IT05566778899', '05566778899',
                'Via Cassia km 24', '00063', 'Campagnano di Roma', 'RM', 'approvata')"
    );

    FDataBase::executeStatement(
        "INSERT INTO azienda_noleggio (id, nome_societa, partita_iva, codice_fiscale,
            indirizzo, cap, comune, provincia, affiliazione) VALUES
            (4, 'Speed Rent SPA', 'IT09876543210', '09876543210',
                'Via dei Motori 10', '20900', 'Monza',              'MB', 'approvata'),
            (9, 'Track Rent SRL', 'IT04433221100', '04433221100',
                'Via delle Corse 7', '00063', 'Campagnano di Roma', 'RM', 'approvata')"
    );

    // Circuiti (id espliciti: riferiti da veicoli e sessioni demo). La tariffa
    // di accesso NON è più sul circuito: viene definita su ogni sessione.
    FDataBase::executeStatement(
        "INSERT INTO circuito (id, gestore_id, nome_circuito, localita, indirizzo, lunghezza_km,
            descrizione, tipologia_veicoli, numero_box, telefono, email, sito_web) VALUES
            (1, 3, 'Autodromo di Monza',  'Monza (MB)', 'Via Vedano 5, Monza', 5.793,
                'Tempio della velocita.', 'entrambi', 30, '+39 039 2482 1', 'info@monza-circuit.it', 'https://www.monzanet.it'),
            (2, 3, 'Mugello Circuit',     'Scarperia e San Piero (FI)', 'Scarperia, Firenze', 5.245,
                'Tracciato collinare nel cuore del Mugello.', 'entrambi', 25, '+39 055 849 3030', 'info@mugellocircuit.it', 'https://www.mugellocircuit.it'),
            (3, 3, 'Imola - Enzo Ferrari','Imola (BO)', 'Piazza Ayrton Senna 1, Imola', 4.909,
                'Storico circuito emiliano.', 'auto', 20, '+39 0542 609 111', 'info@autodromoimola.it', 'https://www.autodromoimola.it'),
            (4, 8, 'Autodromo di Vallelunga', 'Campagnano di Roma (RM)', 'Via Cassia km 24, Campagnano', 4.085,
                'Tracciato laziale tecnico e veloce, gestito da Verdi Motorsport.', 'entrambi', 22, '+39 06 901 5501', 'info@vallelunga.it', 'https://www.vallelunga.it')"
    );

    FDataBase::executeStatement(
        "INSERT INTO circuito_foto (circuito_id, path_file, ordine, didascalia) VALUES
            (1, '/uploads/circuiti/1/monza1.jpg', 0, 'Mappa del Circuito'),
            (1, '/uploads/circuiti/1/monza2.jpg', 1, 'F1 Chicane'),
            (1, '/uploads/circuiti/1/monza3.jpg', 2, 'Curva Grande'),
            (1, '/uploads/circuiti/1/monza4.jpg', 3, 'Variante della Roggia'),
            (2, '/uploads/circuiti/2/mugello1.jpg', 0, 'Mappa del Circuito'),
            (2, '/uploads/circuiti/2/mugello2.jpg', 1, 'GT3 San Donato'),
            (2, '/uploads/circuiti/2/mugello3.jpg', 2, 'MotoGP Casanova-Savelli'),
            (2, '/uploads/circuiti/2/mugello4.jpg', 3, 'THE DOCTOR'),
            (3, '/uploads/circuiti/3/imola1.jpg', 0, 'Mappa del Circuito'),
            (3, '/uploads/circuiti/3/imola2.jpg', 1, 'CAmpionato Endurance'),
            (3, '/uploads/circuiti/3/imola3.jpg', 2, 'Variante Villeneuve'),
            (3, '/uploads/circuiti/3/imola4.jpg', 3, 'Porsche Cup'),
            (4, '/uploads/circuiti/4/vallelunga1.jpg', 0, 'Mappa del Circuito'),
            (4, '/uploads/circuiti/4/vallelunga2.jpg', 1, 'Legend Cars'),
            (4, '/uploads/circuiti/4/vallelunga3.jpg', 2, 'Partenza F4'),
            (4, '/uploads/circuiti/4/vallelunga4.jpg', 3, 'Ferrari 488 GT3')"
    );

    // Veicoli a noleggio (id espliciti: riferiti dalle prenotazioni demo/sessione).
    // Il prezzo di listino (EUR) è direttamente sul veicolo.
    FDataBase::executeStatement(
        "INSERT INTO veicolo_noleggio (id, azienda_id, circuito_id, targa, categoria, capienza, potenza_cv, anno, marca, modello, prezzo) VALUES
            (1, 4, 1, 'AA111BB', 'auto', 2, 250, 2023, 'BMW',        'M2',           450.00),
            (2, 4, 1, 'AA222BB', 'auto', 2, 510, 2024, 'Porsche',    '911 GT3',     1200.00),
            (3, 4, 2, 'MM111MM', 'moto', 1, 200, 2024, 'Yamaha',     'YZF-R1',       400.00),
            (4, 4, 3, 'CC333CC', 'auto', 2, 400, 2022, 'Audi',       'RS3',          600.00),
            (5, 9, 4, 'TR100RR', 'auto', 2, 540, 2023, 'Alfa Romeo', 'Giulia GTAm',  700.00),
            (6, 9, 4, 'TR200RR', 'moto', 1, 215, 2024, 'Ducati',     'Panigale V4',  480.00)"
    );

    // Configurazione della piattaforma (singleton id=1) con i dati fiscali
    // usati come emittente nelle fatture di assicurazione e commissione.
    FDataBase::executeStatement(
        "INSERT INTO configurazione_piattaforma
            (id, prezzo_assicurazione, percentuale_commissione, aliquota_iva,
             fisc_denominazione, fisc_partita_iva, fisc_codice_fiscale, fisc_indirizzo)
         VALUES
            (1, 25.00, 10.00, 22.00,
             'TrackPortal S.r.l.', 'IT12345678903', '12345678903',
             'Via Torino 51, 20123 Milano (MI)')"
    );

    echo "Dati base inseriti.\n";
    echo "Password per tutti gli account seed: {$plain}\n";
    echo "Account disponibili:\n";
    foreach (FDataBase::executeQuery('SELECT email, ruolo FROM account ORDER BY id')->fetchAllAssociative() as $r) {
        echo " - {$r['email']}  ({$r['ruolo']})\n";
    }
    echo "\n";
}

// =====================================================================
// FASE 2 - DATI DEMO: SESSIONI CON PRENOTAZIONI (ultimi 6 mesi)
// =====================================================================
//
// Ogni prenotazione confermata appartiene SEMPRE a una sessione reale del
// calendario (amatoriale o professionistica): non esistono prenotazioni
// "orfane" che apparirebbero come slot "Pren." non cliccabili. Le sessioni
// passate sono cliccabili dal gestore e ne mostrano i piloti; le prenotazioni,
// distribuite sugli ultimi 6 mesi, popolano i grafici delle dashboard.
//
// Idempotente: sessioni marcate '[seed-demo]' nelle note, prenotazioni con
// codice 'DEMO-%'; rigenerate a ogni esecuzione (rimuove anche i marker
// 'SESS-%' / '[seed-sessioni]' di versioni precedenti del seed).

$markerDemo          = '[seed-demo]';
$prezzoAssicurazione = FPersistentManager::configurazionePiattaformaPrezzoAssicurazione();

// Prima delle prenotazioni demo vanno rimossi i loro documenti fiscali: la FK
// è ON DELETE SET NULL, quindi cancellare le prenotazioni li lascerebbe orfani
// (le righe seguono in CASCADE). Così il seed resta idempotente anche sui doc.
FDataBase::executeStatement(
    "DELETE df FROM documento_fiscale df
     JOIN prenotazione p ON p.id = df.prenotazione_id
     WHERE p.codice_identificativo LIKE 'DEMO-%' OR p.codice_identificativo LIKE 'SESS-%'"
);
$prenRimosse = FDataBase::executeStatement(
    "DELETE FROM prenotazione WHERE codice_identificativo LIKE 'DEMO-%' OR codice_identificativo LIKE 'SESS-%'"
);
$sessRimosse = FDataBase::executeStatement(
    "DELETE FROM sessione WHERE note LIKE '%[seed-demo]%' OR note LIKE '%[seed-sessioni]%'"
);

// Circuiti (qualunque gestore). La tariffa di accesso è per SESSIONE: alle
// sessioni demo viene assegnata una tariffa indicativa per circuito.
$tariffePerCircuito = [1 => 220.00, 2 => 280.00, 3 => 250.00, 4 => 200.00];
$circuiti = FDataBase::executeQuery(
    'SELECT id FROM circuito ORDER BY id'
)->fetchAllAssociative();

// Piloti con almeno una carta di credito, raggruppati per la categoria di
// sessione che possono prenotare (amatoriale | professionistica).
$perCategoria = ['amatoriale' => [], 'professionistica' => []];
foreach (FDataBase::executeQuery(
    'SELECT pl.id AS pilota_id, pl.categoria, MIN(cc.id) AS carta
     FROM pilota pl JOIN carta_credito cc ON cc.pilota_id = pl.id
     GROUP BY pl.id, pl.categoria'
)->fetchAllAssociative() as $p) {
    $cat = ((string) $p['categoria'] === 'professionista') ? 'professionistica' : 'amatoriale';
    $perCategoria[$cat][] = $p;
}

// Veicoli a noleggio per circuito (il prezzo di listino è sul veicolo).
$veicoliPerCircuito = [];
foreach (FDataBase::executeQuery(
    'SELECT id, circuito_id, prezzo AS importo FROM veicolo_noleggio'
)->fetchAllAssociative() as $v) {
    $veicoliPerCircuito[(int) $v['circuito_id']][] = $v;
}

if ($circuiti === [] || ($perCategoria['amatoriale'] === [] && $perCategoria['professionistica'] === [])) {
    echo "Fase 2 saltata: servono almeno un circuito e un pilota con carta.\n";
} else {
    // Fasce d'inizio comprese in ORE_GIORNO (08-19); ogni sessione dura 2 ore.
    $oreSlot = [9, 10, 11, 13, 14, 15, 16, 17];
    // Prenotazioni totali per mese (dal più vecchio al più recente): trend crescente.
    $perMese = [16, 22, 27, 34, 41, 33];

    $insertSess = 'INSERT INTO sessione (circuito_id, inizio, fine, tariffa_accesso, posti_max, posti_per_box, note, stato)
                   VALUES (:c, :ini, :fin, :tariffa, :pmax, :pbox, :note, :stato)';
    $insertPren = "INSERT INTO prenotazione
        (codice_identificativo, pilota_id, sessione_id, veicolo_noleggio_id, targa_veicolo,
         numero_box, assicurazione,
         prezzo_importo, prezzo_valuta, imponibile_accesso, imponibile_noleggio,
         imponibile_assicurazione, stato, data_inserimento, carta_credito_id)
        VALUES
        (:cod, :pil, :sess, :vei, :targa, :box, :ass,
         :imp, :val, :imp_acc, :imp_nol, :imp_ass, 'confermata', :data, :carta)";

    $oggi     = new DateTimeImmutable('today');
    $postiBox = 2;

    mt_srand(20260624); // deterministico: stesso dataset a ogni esecuzione

    $seq        = 0;
    $totSess    = 0;
    $totPren    = 0;
    $conVeicolo = 0;
    $conAssic   = 0;

    for ($m = count($perMese) - 1; $m >= 0; $m--) {
        // $m=0 → mese corrente; valori più grandi → mesi più vecchi.
        $meseRef      = $oggi->modify('first day of -' . $m . ' months');
        $giorniMax    = (int) $meseRef->format('t');
        // Nel mese corrente non superare il giorno odierno (niente sessioni future).
        $limiteGiorno = ($m === 0) ? min($giorniMax, (int) $oggi->format('d')) : $giorniMax;
        $target       = $perMese[count($perMese) - 1 - $m];

        $slotUsati = [];   // "cid-Y-m-d H:i" occupati: niente sessioni sovrapposte sullo stesso circuito
        $fatte     = 0;
        $guardia   = 0;
        while ($fatte < $target && $guardia++ < 1000) {
            $cir     = $circuiti[array_rand($circuiti)];
            $cid     = (int) $cir['id'];
            $tariffa = (float) ($tariffePerCircuito[$cid] ?? 200.00);

            $giorno = mt_rand(1, max(1, $limiteGiorno));
            $ora    = $oreSlot[array_rand($oreSlot)];
            $inizio = $meseRef->setDate(
                (int) $meseRef->format('Y'),
                (int) $meseRef->format('n'),
                $giorno
            )->setTime($ora, 0);
            $fine = $inizio->modify('+2 hours');

            // Riserva le due ore coperte: evita sessioni sovrapposte sullo stesso circuito.
            $k0 = $cid . '-' . $inizio->format('Y-m-d H:i');
            $k1 = $cid . '-' . $inizio->modify('+1 hour')->format('Y-m-d H:i');
            if (isset($slotUsati[$k0]) || isset($slotUsati[$k1])) {
                continue;
            }
            $slotUsati[$k0] = true;
            $slotUsati[$k1] = true;

            $catSess  = (mt_rand(0, 1) === 0) ? 'amatoriale' : 'professionistica';
            $altraCat = ($catSess === 'amatoriale') ? 'professionistica' : 'amatoriale';
            // Pool piloti: prima quelli della categoria ammessa, poi gli altri.
            $pool = array_merge($perCategoria[$catSess], $perCategoria[$altraCat]);
            shuffle($pool);

            // Quante prenotazioni in questa sessione: 1..4, senza superare il target o il pool.
            $quanti   = max(1, min(4, count($pool), $target - $fatte));
            $postiMax = mt_rand($quanti, $quanti + 2); // qualche posto libero residuo

            FDataBase::executeStatement($insertSess, [
                ':c'       => $cid,
                ':ini'     => $inizio->format('Y-m-d H:i:s'),
                ':fin'     => $fine->format('Y-m-d H:i:s'),
                ':tariffa' => number_format($tariffa, 2, '.', ''),
                ':pmax'    => $postiMax,
                ':pbox'    => $postiBox,
                ':note'    => 'Sessione dimostrativa ' . $markerDemo,
                ':stato'   => $catSess,
            ]);
            $sessioneId = (int) FDataBase::getInstance()->lastInsertId();
            $totSess++;

            $veicoli = $veicoliPerCircuito[$cid] ?? [];
            for ($k = 0; $k < $quanti; $k++) {
                $pil   = $pool[$k];
                $assic = mt_rand(1, 100) <= 30;

                if ($veicoli !== [] && mt_rand(1, 100) <= 45) {
                    $v        = $veicoli[array_rand($veicoli)];
                    $vid      = (int) $v['id'];
                    $importoV = (float) $v['importo'];
                    $targa    = null;
                    $conVeicolo++;
                } else {
                    $vid      = null;
                    $importoV = 0.0;
                    $targa    = 'AA' . str_pad((string) mt_rand(100, 999), 3, '0', STR_PAD_LEFT) . 'BB';
                }

                $importo = $tariffa + $importoV + ($assic ? $prezzoAssicurazione : 0.0);
                if ($assic) {
                    $conAssic++;
                }

                // L'inserimento avviene poco prima della sessione, restando nello stesso mese.
                $dataIns = $inizio->modify('-' . mt_rand(0, 4) . ' days');
                if ($dataIns < $meseRef) {
                    $dataIns = $meseRef;
                }

                $seq++;
                FDataBase::executeStatement($insertPren, [
                    ':cod'       => sprintf('DEMO-%05d', $seq),
                    ':pil'       => (int) $pil['pilota_id'],
                    ':sess'      => $sessioneId,
                    ':vei'       => $vid,
                    ':targa'     => $targa,
                    ':box'       => ($k % $postiBox) + 1,
                    ':ass'       => $assic ? 1 : 0,
                    ':imp'       => number_format($importo, 2, '.', ''),
                    ':val'       => 'EUR',
                    // Scomposizione imponibili (nessuno sconto nei dati demo): la
                    // somma coincide con prezzo_importo. Serve a FFatturazione per
                    // emettere le fatture dimostrative (accesso / noleggio / assic.).
                    ':imp_acc'   => number_format($tariffa, 2, '.', ''),
                    ':imp_nol'   => number_format($importoV, 2, '.', ''),
                    ':imp_ass'   => number_format($assic ? $prezzoAssicurazione : 0.0, 2, '.', ''),
                    ':data'      => $dataIns->format('Y-m-d H:i:s'),
                    ':carta'     => (int) $pil['carta'],
                ]);
                $fatte++;
                $totPren++;
            }
        }
    }

    // Documenti fiscali dimostrativi: emette le fatture (accesso pista del gestore
    // circuiti, noleggio dell'azienda, assicurazione della piattaforma) per ogni
    // prenotazione demo. Idempotente: salta le prenotazioni già fatturate. Rende
    // scaricabile la fattura dallo storico e popola le pagine "Documenti".
    $prenDemo = FDataBase::executeQuery(
        "SELECT id FROM prenotazione WHERE codice_identificativo LIKE 'DEMO-%' AND stato = 'confermata'"
    )->fetchFirstColumn();
    $fattureEmesse = 0;
    foreach ($prenDemo as $prenId) {
        try {
            FPersistentManager::fatturazioneEmettiPerPrenotazione((int) $prenId);
            $fattureEmesse++;
        } catch (Throwable $e) {
            echo "  ! fattura prenotazione {$prenId} non emessa: {$e->getMessage()}\n";
        }
    }

    echo "Prenotazioni dimostrative rimosse: {$prenRimosse}\n";
    echo "Sessioni dimostrative rimosse:     {$sessRimosse}\n";
    echo "Sessioni dimostrative create:      {$totSess}\n";
    echo "Prenotazioni create (in sessione): {$totPren}\n";
    echo "Prenotazioni fatturate (doc. fiscali): {$fattureEmesse}\n";
    echo "  - con veicolo di noleggio: {$conVeicolo}\n";
    echo "  - con assicurazione:       {$conAssic}\n";
    echo "Prezzo assicurazione usato:  " . number_format($prezzoAssicurazione, 2, ',', '.') . " EUR\n";
    echo "\nOgni prenotazione appartiene a una sessione reale: nessuno slot 'Pren.' orfano.\n";
    echo "Le sessioni passate sono cliccabili dal calendario del gestore e mostrano i piloti.\n";
    echo "Apri le dashboard (gestore / azienda / admin) per i grafici su 6 mesi.\n";
}

// =====================================================================
// FASE 3 - DATI DEMO: PROMOZIONI APPLICABILI ALLE PRENOTAZIONI
// =====================================================================
//
// Promozioni dimostrative pensate per essere realmente applicabili nel wizard
// di prenotazione del pilota: appaiono nel riepilogo e scontano il totale.
// Coprono i tre casi gestiti da CPrenotazioneSessione (promozione automatica):
//   - sconto percentuale legato a un circuito e a un periodo di validità;
//   - sconto a importo fisso sempre valido (nessuna finestra temporale);
//   - sconto fedeltà (soglia sullo storico del pilota su quel circuito);
//   - sconto legato a uno specifico veicolo a noleggio.
//
// Idempotente: codici 'DEMOPROMO%', rimossi e ricreati a ogni esecuzione.
// La FK prenotazione.promozione_id è ON DELETE SET NULL: rimuovere le promo non
// intacca le prenotazioni demo già emesse.

$promoRimosse = FDataBase::executeStatement(
    "DELETE FROM promozione WHERE codice LIKE 'DEMOPROMO%'"
);

$oggiPromo  = new DateTimeImmutable('today');
$daIeri7    = $oggiPromo->modify('-7 days')->format('Y-m-d');
$traQuaranta = $oggiPromo->modify('+45 days')->format('Y-m-d');

// Esiste l'entità referenziata? Evita errori di FK se lo schema base è diverso.
$esisteCircuito = static function (int $id): bool {
    return (int) FDataBase::executeQuery(
        'SELECT COUNT(*) FROM circuito WHERE id = :id',
        [':id' => $id]
    )->fetchOne() > 0;
};
$esisteVeicolo = static function (int $id): bool {
    return (int) FDataBase::executeQuery(
        'SELECT COUNT(*) FROM veicolo_noleggio WHERE id = :id',
        [':id' => $id]
    )->fetchOne() > 0;
};

$insertPromo = "INSERT INTO promozione
    (creator_account_id, veicolo_noleggio_id, circuito_id, titolo,
     descrizione, tipo_sconto, valore, codice, data_inizio, data_fine,
     segmento_destinatari, soglia_prenotazioni, stato_promozione)
    VALUES
    (:acc, :vei, :cir, :tit, :descr, :tipo, :val, :cod, :di, :df,
     :seg, :soglia, 'attiva')";

// [creator, veicolo, circuito, titolo, descr, tipo, valore, codice, di, df, segmento, soglia]
$promoDemo = [
    [3, null, 1, 'Sconto Estate Monza −15%',
     'Sconto del 15% sull\'accesso pista per le prenotazioni effettuate nel periodo promozionale.',
     'percentuale', 15.00, 'DEMOPROMO01', $daIeri7, $traQuaranta, 'tutti', null],
    [8, null, 4, 'Benvenuto a Vallelunga −40€',
     'Riduzione fissa di 40€ sul totale della prenotazione a Vallelunga, sempre attiva.',
     'importo', 40.00, 'DEMOPROMO02', null, null, 'tutti', null],
    [3, null, 1, 'Fedeltà Monza −20% (dal 5° accesso)',
     'Sconto fedeltà del 20% riservato ai piloti con almeno 5 prenotazioni concluse su questo circuito.',
     'percentuale', 20.00, 'DEMOPROMO03', null, null, 'tutti', 5],
    [4, 2, null, 'Promo Porsche 911 GT3 −10%',
     'Sconto del 10% applicato quando noleggi la Porsche 911 GT3.',
     'percentuale', 10.00, 'DEMOPROMO04', null, null, 'tutti', null],
];

$promoCreate = 0;
foreach ($promoDemo as [$acc, $vei, $cir, $tit, $descr, $tipo, $val, $cod, $di, $df, $seg, $soglia]) {
    if ($cir !== null && !$esisteCircuito($cir)) {
        continue;
    }
    if ($vei !== null && !$esisteVeicolo($vei)) {
        continue;
    }
    FDataBase::executeStatement($insertPromo, [
        ':acc'    => $acc,
        ':vei'    => $vei,
        ':cir'    => $cir,
        ':tit'    => $tit,
        ':descr'  => $descr,
        ':tipo'   => $tipo,
        ':val'    => number_format($val, 2, '.', ''),
        ':cod'    => $cod,
        ':di'     => $di,
        ':df'     => $df,
        ':seg'    => $seg,
        ':soglia' => $soglia,
    ]);
    $promoCreate++;
}

echo "\nPromozioni dimostrative rimosse: {$promoRimosse}\n";
echo "Promozioni dimostrative create: {$promoCreate}\n";
echo "  Esempio: prenota l'Autodromo di Monza come pilota per vedere lo sconto nel riepilogo.\n";
