<?php

/**
 * caso d'uso Gestione e aggiunta circuiti (uc 8).
 *
 * Operazioni di sistema:
 *  - elencaCircuitiGestiti() → 1a/1b: elenco «I miei circuiti»
 *  - nuovoCircuito()         → 2a/2b: form di aggiunta circuito
 *  - creaCircuito()          → 3a/3b: validazione e conferma dell'aggiunta
 *  - modificaCircuito()      → gestione: form di modifica precompilato
 *  - aggiornaCircuito()      → gestione: salvataggio delle modifiche*/

class CGestioneAggiuntaCircuiti
{
    /** Azione base invocata dall'URL di sezione (senza azione). */
    public const DEFAULT_ACTION = 'elencaCircuitiGestiti';

    /** Tabella URL → metodo*/
    public const ROUTES = [
        'GET nuovo' => 'nuovoCircuito',
        'POST nuovo' => 'creaCircuito',
        'GET {id}/modifica' => 'modificaCircuito',
        'POST {id}/modifica' => 'aggiornaCircuito',
    ];

    private const TIPOLOGIE_VEICOLI = ['entrambi', 'auto', 'moto'];

    /**elenco circuiti gestiti dall'utente loggato */
    public function elencaCircuitiGestiti(): void
    {
        $gestoreId = self::richiediGestoreCircuiti();

        VGestoreCircuiti::mieiCircuiti(
            FPersistentManager::circuitoLoadByGestore($gestoreId),
            FPersistentManager::gestoreCircuitiIsAffiliazioneApprovata($gestoreId),
            FPersistentManager::gestoreCircuitiGetAffiliazione($gestoreId)
        );
    }

    /** form vuoto per l'inserimento di un nuovo circuito (2a/2b). */
    public function nuovoCircuito(): void
    {
        $gestoreId = self::richiediGestoreCircuiti();
        self::richiediAffiliazioneApprovata($gestoreId);

        VGestoreCircuiti::formAggiungiCircuito();
    }

    /**
     * valida i dati, salva il circuito e mostra conferma o errori (3a/3b)*/
    public function creaCircuito(array $datiCircuito = []): void
    {
        $gestoreId = self::richiediGestoreCircuiti();
        self::richiediAffiliazioneApprovata($gestoreId);

        $datiCircuito = $datiCircuito !== [] ? $datiCircuito : self::datiCircuitoDaPost();
        $fotoUploads  = self::fotoUploadsDaRequest();
        $datiCircuito['foto_didascalie'] = self::didascalieDaUploads($fotoUploads);

        $errors = self::erroriRichiesta($datiCircuito, $fotoUploads);
        if ($errors !== []) {
            VGestoreCircuiti::formAggiungiCircuito($datiCircuito, $errors);
            return;
        }

        try {
            VGestoreCircuiti::confermaAggiuntaCircuito(self::salvaNuovoCircuito($gestoreId, $datiCircuito, $fotoUploads));
        } catch (Throwable $ex) {
            VGestoreCircuiti::formAggiungiCircuito($datiCircuito, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

    /** modifica di un circuito esistente del gestore. */
    public function modificaCircuito(int|string $id = 0): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) $id;

        if (!FPersistentManager::circuitoIsDelGestore($circuitoId, $gestoreId)) {
            flash('error', 'Circuito non trovato o non di tua proprietà.');
            redirect('/circuitiGestore');
        }

        $row = FPersistentManager::circuitoLoadById($circuitoId) ?? [];
        VGestoreCircuiti::formModificaCircuito($circuitoId, self::formDaRow($row), $row['foto'] ?? []);
    }

    /**conferma le modifiche apportate ad un circuito esistente */
    public function aggiornaCircuito(int|string $id = 0, array $datiCircuito = []): void
    {
        $gestoreId  = self::richiediGestoreCircuiti();
        $circuitoId = (int) $id;
        $circuito   = self::circuitoDelGestoreOppureRedirect($circuitoId, $gestoreId);

        $datiCircuito = $datiCircuito !== [] ? $datiCircuito : self::datiCircuitoDaPost();
        $fotoUploads  = self::fotoUploadsDaRequest();
        $idsRimuovi   = self::idsFotoDaRimuovere();
        $datiCircuito['foto_didascalie'] = self::didascalieDaUploads($fotoUploads);

        $errors = self::erroriRichiesta($datiCircuito, $fotoUploads, max(0, $circuito->contaFoto() - count($idsRimuovi)));
        if ($errors !== []) {
            self::mostraFormModifica($circuitoId, $datiCircuito, $errors);
            return;
        }

        try {
            self::salvaModificheCircuito($circuito, $datiCircuito, $idsRimuovi, $fotoUploads);
            flash('ok', 'Circuito aggiornato correttamente.');
            redirect('/circuiti/' . $circuitoId);
        } catch (Throwable $ex) {
            self::mostraFormModifica($circuitoId, $datiCircuito, ['Errore durante il salvataggio: ' . $ex->getMessage()]);
        }
    }

     /** Verifica autenticazione e ruolo gestore circuiti; restituisce l'id del gestore. */
    private static function richiediGestoreCircuiti(): int
    {
        return CAuth::idUtenteConRuolo(EGestoreCircuiti::$ruolo);
    }

    /** Blocca la creazione circuiti finché l'affiliazione non è approvata dall'admin. */
    private static function richiediAffiliazioneApprovata(int $gestoreId): void
    {
        if (FPersistentManager::gestoreCircuitiIsAffiliazioneApprovata($gestoreId)) {
            return;
        }

        flash(
            'warn',
            'La tua richiesta di affiliazione è in attesa di approvazione. Non puoi ancora creare circuiti.'
        );
        redirect('/circuitiGestore');
    }

    /** verifica se circuito del gestore, o redirect all'elenco con messaggio. */
    private static function circuitoDelGestoreOppureRedirect(int $circuitoId, int $gestoreId): ECircuito
    {
        $circuito = FPersistentManager::circuitoLoadEntityById($circuitoId);
        if (!$circuito instanceof ECircuito || $circuito->getGestoreId() !== $gestoreId) {
            flash('error', 'Circuito non trovato o non di tua proprietà.');
            redirect('/circuitiGestore');
        }

        return $circuito;
    }

    /**
     * CSRF + validazione campi + validazione foto (con eventuale conteggio
     * delle foto già presenti) */
    private static function erroriRichiesta(array $dati, array $uploads, int $fotoEsistenti = 0): array
    {
        if (!csrf_check(isset($dati['csrf_token']) ? (string) $dati['csrf_token'] : null)) {
            return ['Token CSRF non valido.'];
        }

        return array_merge(
            self::validaDatiCircuito($dati),
            self::validaFotoUploads($uploads, $fotoEsistenti)
        );
    }

    /**Riga DB del circuito appena creato*/
    private static function salvaNuovoCircuito(int $gestoreId, array $dati, array $uploads): array
    {
        $circuito = self::persistiCircuito($gestoreId, $dati);
        self::applicaFotoUploads($circuito, $uploads);
        FPersistentManager::flush();

        return FPersistentManager::circuitoLoadById((int) ($circuito->getId() ?? 0)) ?? [];
    }

    /**salvataggio modifiche di un circuito esistente (dati + foto) */
    private static function salvaModificheCircuito(ECircuito $circuito, array $dati, array $idsRimuovi, array $uploads): void
    {
        self::applicaDatiCircuito($circuito, $dati);
        self::rimuoviFoto($circuito, $idsRimuovi);
        self::applicaFotoUploads($circuito, $uploads);
        FPersistentManager::flush();
    }

    /**funzione per mostrare il form di modifica con i dati precompilati */
    private static function mostraFormModifica(int $circuitoId, array $dati, array $errors): void
    {
        $row = FPersistentManager::circuitoLoadById($circuitoId) ?? [];
        VGestoreCircuiti::formModificaCircuito($circuitoId, $dati, $row['foto'] ?? [], $errors);
    }

    /**Restituisce i dati del circuito provenienti dal POST in un array */
    private static function datiCircuitoDaPost(): array
    {
        return [
            'csrf_token'      => (string) post('csrf_token', ''),
            'nome'            => (string) post('nome', ''),
            'localita'        => (string) post('localita', ''),
            'indirizzo'       => (string) post('indirizzo', ''),
            'lunghezza_km'    => (string) post('lunghezza_km', ''),
            'tipologia'       => (string) post('tipologia', 'entrambi'),
            'numero_box'      => (string) post('numero_box', '0'),
            'telefono'        => (string) post('telefono', ''),
            'email'           => (string) post('email', ''),
            'sito_web'        => (string) post('sito_web', ''),
            'descrizione'     => (string) post('descrizione', ''),
        ];
    }

    /**funzione che valida dati anagrafici, caratteristiche e contatti del circuito */
    private static function validaDatiCircuito(array $dati): array
    {
        return array_merge(
            self::erroriAnagraficaCircuito($dati),
            self::erroriCaratteristicheCircuito($dati),
            self::erroriContattiCircuito($dati)
        );
    }

    /** valida nome, località e indirizzo  */
    private static function erroriAnagraficaCircuito(array $dati): array
    {
        $errors = [];
        $nome   = trim((string) ($dati['nome'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Il nome del circuito è obbligatorio.';
        } elseif (mb_strlen($nome) > 150) {
            $errors[] = 'Il nome del circuito non può superare 150 caratteri.';
        } elseif (!preg_match('/^[\p{L}\p{N}\s\'\-\.]+$/u', $nome)) {
            $errors[] = 'Il nome del circuito contiene caratteri non consentiti.';
        }

        $localita = trim((string) ($dati['localita'] ?? ''));
        if ($localita === '') {
            $errors[] = 'La località è obbligatoria.';
        } elseif (mb_strlen($localita) > 255) {
            $errors[] = 'La località non può superare 255 caratteri.';
        }

        if (mb_strlen(trim((string) ($dati['indirizzo'] ?? ''))) > 255) {
            $errors[] = 'L\'indirizzo non può superare 255 caratteri.';
        }

        return $errors;
    }

    /** Lunghezza tracciato, tipologia veicoli e numero di box.
     * @param array<string, mixed> $dati
     * @return list<string>
     */
    private static function erroriCaratteristicheCircuito(array $dati): array
    {
        $errors  = [];
        $lungRaw = trim((string) ($dati['lunghezza_km'] ?? ''));
        if ($lungRaw !== '' && !is_numeric($lungRaw)) {
            $errors[] = 'La lunghezza del tracciato deve essere un numero valido (es. 5.793).';
        } elseif ($lungRaw !== '' && ((float) $lungRaw <= 0 || (float) $lungRaw > 100)) {
            $errors[] = 'La lunghezza del tracciato deve essere compresa tra 0.001 e 100 km.';
        }

        if (!in_array((string) ($dati['tipologia'] ?? ''), self::TIPOLOGIE_VEICOLI, true)) {
            $errors[] = 'Seleziona una tipologia veicoli valida (auto, moto o entrambi).';
        }

        $boxRaw = trim((string) ($dati['numero_box'] ?? ''));
        if ($boxRaw === '' || !ctype_digit($boxRaw) || (int) $boxRaw < 1) {
            $errors[] = 'Il numero di box deve essere un intero positivo (minimo 1).';
        } elseif ((int) $boxRaw > 999) {
            $errors[] = 'Il numero di box non può superare 999.';
        }

        return $errors;
    }

    /** valida telefono, email e sito web */
    private static function erroriContattiCircuito(array $dati): array
    {
        $errors   = [];
        $telefono = trim((string) ($dati['telefono'] ?? ''));
        if ($telefono === '') {
            $errors[] = 'Il telefono di contatto è obbligatorio.';
        } elseif (mb_strlen($telefono) > 30) {
            $errors[] = 'Il telefono non può superare 30 caratteri.';
        }

        $email = trim((string) ($dati['email'] ?? ''));
        if ($email === '') {
            $errors[] = 'L\'email di contatto è obbligatoria.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Inserisci un indirizzo email valido.';
        } elseif (mb_strlen($email) > 190) {
            $errors[] = 'L\'email non può superare 190 caratteri.';
        }

        return array_merge($errors, self::erroriSitoWeb($dati));
    }

    /** vede se il link al sito è ben formattato*/
    private static function erroriSitoWeb(array $dati): array
    {
        $sitoWeb = trim((string) ($dati['sito_web'] ?? ''));
        if ($sitoWeb === '') {
            return [];
        }
        if (mb_strlen($sitoWeb) > 255) {
            return ['Il sito web non può superare 255 caratteri.'];
        }
        if (!filter_var($sitoWeb, FILTER_VALIDATE_URL)) {
            return ['Inserisci un URL valido per il sito web (es. https://www.esempio.it).'];
        }

        return [];
    }

    /**esegue lo store del circuito assegnando i dati anche alla entity */
    private static function persistiCircuito(int $gestoreId, array $dati): ECircuito
    {
        $lungRaw = trim((string) ($dati['lunghezza_km'] ?? ''));
        $descr   = trim((string) ($dati['descrizione'] ?? ''));
        $sitoWeb = trim((string) ($dati['sito_web'] ?? ''));

        $c = new ECircuito(
            $gestoreId,
            trim((string) $dati['nome']),
            trim((string) $dati['localita']),
            trim((string) ($dati['indirizzo'] ?? '')),
            $lungRaw !== '' ? (float) $lungRaw : null,
            $descr !== '' ? $descr : null,
            (string) $dati['tipologia'],
            (int) $dati['numero_box'],
            trim((string) ($dati['telefono'] ?? '')),
            trim((string) ($dati['email'] ?? '')),
            $sitoWeb !== '' ? $sitoWeb : null
        );
        FPersistentManager::store($c);

        return $c;
    }

    /**
     * Aggiorna i campi di un circuito esistente dai dati del form */
    private static function applicaDatiCircuito(ECircuito $c, array $dati): void
    {
        $lungRaw = trim((string) ($dati['lunghezza_km'] ?? ''));
        $descr   = trim((string) ($dati['descrizione'] ?? ''));
        $sitoWeb = trim((string) ($dati['sito_web'] ?? ''));

        $c->setNomeCircuito(trim((string) $dati['nome']));
        $c->setLocalita(trim((string) $dati['localita']));
        $c->setIndirizzo(trim((string) ($dati['indirizzo'] ?? '')));
        $c->setLunghezzaKm($lungRaw !== '' ? (float) $lungRaw : null);
        $c->setDescrizione($descr !== '' ? $descr : null);
        $c->setTipologiaVeicoli((string) $dati['tipologia']);
        $c->setNumeroBox((int) $dati['numero_box']);
        $c->setTelefono(trim((string) ($dati['telefono'] ?? '')));
        $c->setEmail(trim((string) ($dati['email'] ?? '')));
        $c->setSitoWeb($sitoWeb !== '' ? $sitoWeb : null);
    }

    /**
     * Mappa una riga DB del circuito sulle chiavi attese dal form  */
    private static function formDaRow(array $row): array
    {
        return [
            'nome'            => (string) ($row['nome_circuito'] ?? ''),
            'localita'        => (string) ($row['localita'] ?? ''),
            'indirizzo'       => (string) ($row['indirizzo'] ?? ''),
            'lunghezza_km'    => $row['lunghezza_km'] ?? '',
            'tipologia'       => (string) ($row['tipologia_veicoli'] ?? 'entrambi'),
            'numero_box'      => $row['numero_box'] ?? '',
            'telefono'        => (string) ($row['telefono'] ?? ''),
            'email'           => (string) ($row['email'] ?? ''),
            'sito_web'        => (string) ($row['sito_web'] ?? ''),
            'descrizione'     => (string) ($row['descrizione'] ?? ''),
        ];
    }

    /**
     * Legge gli upload immagine dalla richiesta, accoppiando ogni file alla
     * didascalia corrispondente. Salta gli input vuoti  */
    private static function fotoUploadsDaRequest(): array
    {
        $files = $_FILES['foto'] ?? [];
        if (!is_array($files) || $files === []) {
            return [];
        }

        $didascalie = is_array($_POST['foto_didascalia'] ?? null) ? $_POST['foto_didascalia'] : [];
        $out        = [];
        foreach (FPersistentManager::circuitoFotoNormalizzaFiles($files) as $i => $file) {
            if (FPersistentManager::circuitoFotoFileVuoto($file)) {
                continue;
            }
            $out[] = ['file' => $file, 'didascalia' => trim((string) ($didascalie[$i] ?? ''))];
        }

        return $out;
    }

    /** estrae le didascalie dai file caricati (isola il solo testo)*/
    private static function didascalieDaUploads(array $uploads): array
    {
        return array_map(
            static fn (array $u): string => (string) $u['didascalia'],
            $uploads
        );
    }

    /** Id delle foto selezionate per la rimozione */
    private static function idsFotoDaRimuovere(): array
    {
        $raw = $_POST['rimuovi_foto'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $ids = array_map(static fn ($v): int => (int) $v, $raw);

        return array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    }

    /** controlla che gli upload siano validi e non superino il numero massimo di foto per il singolo circuito*/
     */
    private static function validaFotoUploads(array $uploads, int $esistenti = 0): array
    {
        if ($uploads === []) {
            return [];
        }

        $errors = [];
        if ($esistenti + count($uploads) > FPersistentManager::CIRCUITO_FOTO_MAX_FOTO) {
            $errors[] = 'Puoi avere al massimo ' . FPersistentManager::CIRCUITO_FOTO_MAX_FOTO . ' foto per circuito.';
        }
        foreach ($uploads as $n => $u) {
            $errors = array_merge($errors, self::erroriFoto($n + 1, $u));
        }

        return $errors;
    }

    /**esplicita gli errori di validazione per le foto */
    private static function erroriFoto(int $numero, array $upload): array
    {
        $errors     = [];
        $etichetta  = 'Foto #' . $numero;
        $didascalia = (string) $upload['didascalia'];
        if ($didascalia === '') {
            $errors[] = $etichetta . ': inserisci un nome che descriva l\'immagine.';
        } elseif (mb_strlen($didascalia) > 255) {
            $errors[] = $etichetta . ': il nome non può superare 255 caratteri.';
        }

        try {
            FPersistentManager::circuitoFotoValidaUpload($upload['file']);
        } catch (Throwable $ex) {
            $errors[] = $etichetta . ': ' . $ex->getMessage();
        }

        return $errors;
    }

    /**
     * Salva su disco i file caricati e li aggancia all'aggregato circuito */
    private static function applicaFotoUploads(ECircuito $circuito, array $uploads): void
    {
        $circuitoId = (int) $circuito->getId();
        foreach ($uploads as $u) {
            $webPath = FPersistentManager::circuitoFotoSalva($circuitoId, $u['file']);
            $circuito->addFoto($webPath, $u['didascalia'] !== '' ? $u['didascalia'] : null);
        }
    }

    /**
     * Rimuove dall'aggregato e dal disco le foto indicate per id per la rimozione */
    private static function rimuoviFoto(ECircuito $circuito, array $idsRimuovi): void
    {
        if ($idsRimuovi === []) {
            return;
        }

        foreach ($circuito->getFotoOrdinate() as $foto) {
            if (in_array((int) $foto->getId(), $idsRimuovi, true)) {
                FPersistentManager::circuitoFotoElimina($foto->getPathFile());
                $circuito->removeFoto($foto);
            }
        }
    }
}