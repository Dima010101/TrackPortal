<?php

/** classe di controllo per la gestione degli account */
class CGestioneAccount
{
    /** azione di sezione usata quando l'URL non indica un metodo */
    public const DEFAULT_ACTION = 'gestisciAccount';

    /** tabella rotte risolta da CFrontController */
    public const ROUTES = [
        'POST riepilogo' => 'riepilogaModificheAccount',
        'POST salva' => 'aggiornaAccount',
        'POST documenti' => 'caricaDocumenti',
        'POST documenti/elimina' => 'eliminaDocumento',
        'GET {id}/{p}' => 'visualizzaDocumentoPilota',
    ];

    private const SESSION_KEY = 'gestione_account_pending';

    public function gestisciAccount(): void
    {
        [$userId, $ruolo, $dashBack] = self::richiediUtenteAutenticato();

        VAccount::gestioneAccount(
            self::caricaProfilo($userId, $ruolo),
            self::caricaProfiloRuolo($userId, $ruolo),
            $dashBack
        );
    }

    public function riepilogaModificheAccount(): void
    {
        [$userId, $ruolo, $dashBack] = self::richiediUtenteAutenticato();
        $nuovi_dati = self::datiDaPost();

        $profiloAttuale = self::caricaProfilo($userId, $ruolo);
        $extraAttuale   = self::caricaProfiloRuolo($userId, $ruolo);

        $errors = !csrf_check(isset($nuovi_dati['csrf_token']) ? (string) $nuovi_dati['csrf_token'] : null)
            ? ['Token CSRF non valido. Ricarica la pagina e riprova.']
            : self::validaDatiAccount($nuovi_dati, $userId, $ruolo);
        if ($errors !== []) {
            VAccount::gestioneAccount($profiloAttuale, $extraAttuale, $dashBack, $nuovi_dati, $errors);
            return;
        }

        $pending = self::normalizzaDatiPending($nuovi_dati, $userId, $ruolo);
        if (!self::haModifiche($profiloAttuale, $extraAttuale, $pending, $ruolo)) {
            VAccount::gestioneAccount($profiloAttuale, $extraAttuale, $dashBack, $nuovi_dati, ['Nessuna modifica rispetto ai dati attuali.']);
            return;
        }

        Session::put(self::SESSION_KEY, $pending);
        VAccount::riepilogoModifiche($profiloAttuale, $extraAttuale, $pending, $ruolo, $dashBack);
    }

    public function aggiornaAccount(): void
    {
        [$userId, $ruolo, $dashBack] = self::richiediUtenteAutenticato();

        $errors  = self::erroriRichiestaPost('conferma le modifiche dal riepilogo');
        $pending = Session::get(self::SESSION_KEY);
        if ($errors === [] && (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== $userId)) {
            $errors[] = 'Nessuna modifica in sospeso da salvare. La sessione potrebbe essere scaduta: ripeti la procedura.';
        }
        if ($errors !== []) {
            VAccount::confermaModifica(self::caricaProfilo($userId, $ruolo), $dashBack, $errors);
            return;
        }

        try {
            self::applicaModifiche($userId, $ruolo, $pending);
            Session::forget(self::SESSION_KEY);
            CAuth::invalidaCacheDominio();
            VAccount::confermaModifica(self::caricaProfilo($userId, $ruolo), $dashBack);
        } catch (Throwable) {
            VAccount::confermaModifica(self::caricaProfilo($userId, $ruolo), $dashBack, ['Errore durante il salvataggio. Riprova tra qualche istante.']);
        }
    }

    public function caricaDocumenti(): void
    {
        $uid      = self::pilotaLoggatoOppureRedirect();
        $certFile = $_FILES['certificato_medico'] ?? [];
        $licFile  = $_FILES['licenza_pdf'] ?? [];

        $errors = self::erroriUploadDocumenti($certFile, $licFile);
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect('/account');
        }

        try {
            self::sostituisciDocumenti($uid, $certFile, $licFile);
        } catch (Throwable) {
            flash('error', 'Errore durante il salvataggio dei documenti. Riprova tra qualche istante.');
        }

        redirect('/account');
    }

    public function eliminaDocumento(): void
    {
        $uid  = self::pilotaLoggatoOppureRedirect();
        $tipo = (string) post('tipo', '');

        $errors = self::erroriRichiestaPost('riprova dal tuo account');
        if ($errors === [] && !in_array($tipo, FPersistentManager::DOCUMENTO_PILOTA_TIPI, true)) {
            $errors[] = 'Documento non valido.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            redirect('/account');
        }

        try {
            $vecchio = FPersistentManager::pilotaRimuoviDocumento($uid, $tipo);
            if ($vecchio !== null && $vecchio !== '') {
                FPersistentManager::documentoPilotaElimina($vecchio);
            }
            flash('warn', 'Documento eliminato. Per poter prenotare devi caricare entrambi i documenti (certificato medico e patente/licenza).');
        } catch (Throwable) {
            flash('error', 'Errore durante l\'eliminazione del documento. Riprova tra qualche istante.');
        }

        redirect('/account');
    }

    /** serve i PDF riservati del pilota solo ad admin o proprietario */
    public function visualizzaDocumentoPilota(int|string $pilota = 0, string $tipo = ''): void
    {
        CAuth::richiediLogin();

        $pilotaId = (int) $pilota;
        if ($pilotaId < 1 || !in_array($tipo, FPersistentManager::DOCUMENTO_PILOTA_TIPI, true)) {
            VError::nonTrovato();
            return;
        }
        if (!self::autorizzatoAlDocumento($pilotaId)) {
            VError::accessoNegato([EAmministratore::$ruolo, EPilota::$ruolo]);
            return;
        }

        $pilotaRow = FPersistentManager::pilotaLoadByUtente($pilotaId);
        $colonna   = $tipo === 'certificato_medico' ? 'certificato_medico_path' : 'licenza_path';
        if ($pilotaRow === null
            || !FPersistentManager::documentoPilotaInvia($pilotaId, $tipo, (string) ($pilotaRow[$colonna] ?? ''))) {
            VError::nonTrovato();
        }
    }

    /** ritorna id ruolo e url dashboard dell'utente autenticato */
    private static function richiediUtenteAutenticato(): array
    {
        CAuth::richiediLogin();
        $user = CAuth::utenteCorrente();

        return [(int) $user['id'], (string) $user['ruolo'], '/dashboard'];
    }

    private static function pilotaLoggatoOppureRedirect(): int
    {
        CAuth::richiediLogin();
        $user = CAuth::utenteCorrente();
        if ((string) ($user['ruolo'] ?? '') !== EPilota::$ruolo) {
            flash('error', 'Operazione non disponibile per il tuo profilo.');
            redirect('/account');
        }

        return (int) $user['id'];
    }

    /** vero solo per amministratore o pilota proprietario */
    private static function autorizzatoAlDocumento(int $pilotaId): bool
    {
        $user  = CAuth::utenteCorrente();
        $ruolo = (string) ($user['ruolo'] ?? '');

        return $ruolo === EAmministratore::$ruolo
            || ($ruolo === EPilota::$ruolo && (int) ($user['id'] ?? 0) === $pilotaId);
    }

    private static function erroriRichiestaPost(string $istruzione): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Richiesta non valida: ' . $istruzione . '.'];
        }
        if (!csrf_check(post('csrf_token'))) {
            return ['Token CSRF non valido. Ricarica la pagina e riprova.'];
        }

        return [];
    }

    private static function erroriUploadDocumenti(array $certFile, array $licFile): array
    {
        $errors = self::erroriRichiestaPost('riprova dal tuo account');
        $vuotoC = FPersistentManager::documentoPilotaFileVuoto($certFile);
        $vuotoL = FPersistentManager::documentoPilotaFileVuoto($licFile);
        if ($errors === [] && $vuotoC && $vuotoL) {
            $errors[] = 'Seleziona almeno un documento PDF da caricare.';
        }

        if ($errors === []) {
            try {
                foreach ([[$certFile, $vuotoC], [$licFile, $vuotoL]] as [$file, $vuoto]) {
                    if (!$vuoto) {
                        FPersistentManager::documentoPilotaValidaUpload($file);
                    }
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    /** salva i nuovi PDF elimina i sostituiti e notifica l'esito */
    private static function sostituisciDocumenti(int $uid, array $certFile, array $licFile): void
    {
        $attuale = FPersistentManager::pilotaLoadByUtente($uid) ?? [];
        $haCert  = !FPersistentManager::documentoPilotaFileVuoto($certFile);
        $haLic   = !FPersistentManager::documentoPilotaFileVuoto($licFile);

        $nuovoCert = $haCert ? FPersistentManager::documentoPilotaSalva($uid, 'certificato_medico', $certFile) : null;
        $nuovoLic  = $haLic ? FPersistentManager::documentoPilotaSalva($uid, 'licenza', $licFile) : null;
        FPersistentManager::pilotaAggiornaDocumenti($uid, $nuovoCert, $nuovoLic);
        FPersistentManager::pilotaUpdateDocumentiStato($uid, EPilota::DOC_IN_ATTESA);

        self::eliminaFileSostituito((string) ($attuale['certificato_medico_path'] ?? ''), $nuovoCert);
        self::eliminaFileSostituito((string) ($attuale['licenza_path'] ?? ''), $nuovoLic);
        self::notificaEsitoUpload($uid);
    }

    private static function eliminaFileSostituito(string $vecchio, ?string $nuovo): void
    {
        if ($nuovo !== null && $vecchio !== '' && $vecchio !== $nuovo) {
            FPersistentManager::documentoPilotaElimina($vecchio);
        }
    }

    /** avvisa l'amministrazione se i documenti sono completi */
    private static function notificaEsitoUpload(int $uid): void
    {
        $dopo = FPersistentManager::pilotaLoadByUtente($uid) ?? [];
        if (!FPersistentManager::affiliazionePilotaDocumentiCompleti($dopo)) {
            flash('warn', 'Documento caricato. Per poter prenotare devi caricare ENTRAMBI i documenti (certificato medico e patente/licenza).');
            return;
        }

        try {
            FPersistentManager::notificheNotificaAdminDocumentiPilota($uid);
        } catch (Throwable $e) {
            error_log('Notifica admin documenti pilota: ' . $e->getMessage());
        }
        flash('ok', 'Documenti aggiornati. Verranno riesaminati dall\'amministrazione prima di poter prenotare.');
    }

    private static function caricaProfilo(int $userId, string $ruolo): array
    {
        $row = FPersistentManager::accountLoadById($userId) ?? [];
        unset($row['password']);

        $sessione = CAuth::utenteCorrente() ?? [];

        return array_merge($row, [
            'id'      => $userId,
            'nome'    => (string) ($row['nome'] ?? $sessione['nome'] ?? ''),
            'cognome' => (string) ($row['cognome'] ?? $sessione['cognome'] ?? ''),
            'email'   => (string) ($row['email'] ?? $sessione['email'] ?? ''),
            'ruolo'   => $ruolo,
        ]);
    }

    private static function caricaProfiloRuolo(int $userId, string $ruolo): ?array
    {
        return match ($ruolo) {
            EPilota::$ruolo          => FPersistentManager::pilotaLoadByUtente($userId),
            EGestoreCircuiti::$ruolo => FPersistentManager::gestoreCircuitiLoadByUtente($userId),
            EGestoreNoleggio::$ruolo => FPersistentManager::gestoreNoleggioLoadByUtente($userId),
            default                  => null,
        };
    }

    private static function datiDaPost(): array
    {
        return [
            'csrf_token' => (string) post('csrf_token', ''),
            'nome' => (string) post('nome', ''), 'cognome' => (string) post('cognome', ''),
            'email' => (string) post('email', ''),
            'password_vec' => (string) post('password_vec', ''),
            'password_new' => (string) post('password_new', ''),
            'password_confirm' => (string) post('password_confirm', ''),
            'categoria' => (string) post('categoria', ''), 'licenza' => (string) post('licenza', ''),
            'scadenza_licenza' => (string) post('scadenza_licenza', ''),
            'codice_fiscale' => (string) post('codice_fiscale', ''),
            'indirizzo' => (string) post('indirizzo', ''), 'cap' => (string) post('cap', ''),
            'comune' => (string) post('comune', ''), 'provincia' => (string) post('provincia', ''),
            'fatt_indirizzo' => (string) post('fatt_indirizzo', ''), 'fatt_cap' => (string) post('fatt_cap', ''),
            'fatt_comune' => (string) post('fatt_comune', ''), 'fatt_provincia' => (string) post('fatt_provincia', ''),
        ];
    }

    private static function validaDatiAccount(array $dati, int $userId, string $ruolo): array
    {
        $errors = array_merge(
            self::erroriAnagrafica($dati),
            self::erroriEmail($dati, $userId),
            self::erroriCambioPassword($dati, $userId)
        );
        if ($ruolo === EPilota::$ruolo) {
            $errors = array_merge($errors, self::erroriProfiloPilota($dati), self::erroriIndirizziPilota($dati));
        }

        return $errors;
    }

    private static function erroriAnagrafica(array $dati): array
    {
        $errors = [];
        $nome   = trim((string) ($dati['nome'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Il nome è obbligatorio.';
        } elseif (mb_strlen($nome) > 100) {
            $errors[] = 'Il nome non può superare 100 caratteri.';
        }

        $cognome = trim((string) ($dati['cognome'] ?? ''));
        if ($cognome === '') {
            $errors[] = 'Il cognome è obbligatorio.';
        } elseif (mb_strlen($cognome) > 100) {
            $errors[] = 'Il cognome non può superare 100 caratteri.';
        }

        return $errors;
    }

    private static function erroriEmail(array $dati, int $userId): array
    {
        $email = trim((string) ($dati['email'] ?? ''));
        if ($email === '') {
            return ['L\'email è obbligatoria.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Inserisci un indirizzo email valido.'];
        }

        $esistente = FPersistentManager::accountLoadByEmail($email);
        if ($esistente !== null && (int) ($esistente['id'] ?? 0) !== $userId) {
            return ['L\'indirizzo email è già associato a un altro account.'];
        }

        return [];
    }

    /** valida il cambio password solo se almeno un campo è compilato */
    private static function erroriCambioPassword(array $dati, int $userId): array
    {
        $vec = (string) ($dati['password_vec'] ?? '');
        $new = (string) ($dati['password_new'] ?? '');
        $cnf = (string) ($dati['password_confirm'] ?? '');
        if ($vec === '' && $new === '' && $cnf === '') {
            return [];
        }

        $errors = self::erroriPasswordAttuale($vec, $userId);
        if ($new === '') {
            $errors[] = 'Inserisci la nuova password.';
        } elseif (!self::passwordSufficientementeComplessa($new)) {
            $errors[] = 'La nuova password deve avere almeno 8 caratteri e contenere lettere e numeri.';
        }
        if ($cnf === '') {
            $errors[] = 'Conferma la nuova password.';
        } elseif ($new !== $cnf) {
            $errors[] = 'La conferma password non coincide con la nuova password.';
        }

        return $errors;
    }

    private static function erroriPasswordAttuale(string $passwordAttuale, int $userId): array
    {
        if ($passwordAttuale === '') {
            return ['Per cambiare la password inserisci quella attuale.'];
        }

        $row = FPersistentManager::accountLoadById($userId);
        if ($row === null || !password_verify($passwordAttuale, (string) ($row['password'] ?? ''))) {
            return ['La password attuale non è corretta.'];
        }

        return [];
    }

    private static function erroriProfiloPilota(array $dati): array
    {
        $errors = [];
        if (!in_array((string) ($dati['categoria'] ?? ''), ['amatoriale', 'professionista'], true)) {
            $errors[] = 'Seleziona una categoria pilota valida.';
        }

        $scadenza = trim((string) ($dati['scadenza_licenza'] ?? ''));
        if ($scadenza !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $scadenza)) {
            $errors[] = 'La scadenza licenza deve essere nel formato AAAA-MM-GG.';
        }

        $codiceFiscale = codice_fiscale_normalizza((string) ($dati['codice_fiscale'] ?? ''));
        if ($codiceFiscale === '') {
            $errors[] = 'Il codice fiscale è obbligatorio (intesta le tue fatture).';
        } elseif (!codice_fiscale_valido($codiceFiscale)) {
            $errors[] = 'Codice fiscale non valido: usa il formato italiano di 16 caratteri.';
        }

        return $errors;
    }

    private static function erroriIndirizziPilota(array $dati): array
    {
        return array_merge(
            valida_blocco_indirizzo(
                (string) ($dati['indirizzo'] ?? ''),
                (string) ($dati['cap'] ?? ''),
                (string) ($dati['comune'] ?? ''),
                (string) ($dati['provincia'] ?? ''),
                'di residenza'
            ),
            valida_blocco_indirizzo(
                (string) ($dati['fatt_indirizzo'] ?? ''),
                (string) ($dati['fatt_cap'] ?? ''),
                (string) ($dati['fatt_comune'] ?? ''),
                (string) ($dati['fatt_provincia'] ?? ''),
                'di fatturazione'
            )
        );
    }

    private static function passwordSufficientementeComplessa(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }

        return (bool) preg_match('/[A-Za-z]/', $password) && (bool) preg_match('/\d/', $password);
    }

    private static function normalizzaDatiPending(array $dati, int $userId, string $ruolo): array
    {
        $pending = [
            'user_id'         => $userId,
            'ruolo'           => $ruolo,
            'nome'            => trim((string) $dati['nome']),
            'cognome'         => trim((string) $dati['cognome']),
            'email'           => trim((string) $dati['email']),
            'cambia_password' => false,
        ];

        if ((string) ($dati['password_new'] ?? '') !== '') {
            $pending['cambia_password'] = true;
            $pending['password_hash']   = password_hash((string) $dati['password_new'], PASSWORD_BCRYPT);
        }
        if ($ruolo === EPilota::$ruolo) {
            $pending['pilota'] = self::pendingPilota($dati);
        }

        return $pending;
    }

    private static function pendingPilota(array $dati): array
    {
        return [
            'categoria'        => (string) $dati['categoria'],
            'licenza'          => trim((string) ($dati['licenza'] ?? '')) ?: null,
            'scadenza_licenza' => trim((string) ($dati['scadenza_licenza'] ?? '')) ?: null,
            'codice_fiscale'   => codice_fiscale_normalizza((string) ($dati['codice_fiscale'] ?? '')),
            'indirizzo'        => trim((string) ($dati['indirizzo'] ?? '')),
            'cap'              => trim((string) ($dati['cap'] ?? '')),
            'comune'           => trim((string) ($dati['comune'] ?? '')),
            'provincia'        => provincia_normalizza((string) ($dati['provincia'] ?? '')),
            'fatt_indirizzo'   => trim((string) ($dati['fatt_indirizzo'] ?? '')),
            'fatt_cap'         => trim((string) ($dati['fatt_cap'] ?? '')),
            'fatt_comune'      => trim((string) ($dati['fatt_comune'] ?? '')),
            'fatt_provincia'   => provincia_normalizza((string) ($dati['fatt_provincia'] ?? '')),
        ];
    }

    private static function haModifiche(array $profiloAttuale, ?array $extraAttuale, array $pending, string $ruolo): bool
    {
        foreach (['nome', 'cognome', 'email'] as $campo) {
            if ((string) ($profiloAttuale[$campo] ?? '') !== (string) $pending[$campo]) {
                return true;
            }
        }
        if (!empty($pending['cambia_password'])) {
            return true;
        }
        if ($ruolo !== EPilota::$ruolo || !isset($pending['pilota']) || !is_array($extraAttuale)) {
            return false;
        }

        foreach ($pending['pilota'] as $campo => $valore) {
            if ((string) ($extraAttuale[$campo] ?? '') !== (string) $valore) {
                return true;
            }
        }

        return false;
    }

    private static function applicaModifiche(int $userId, string $ruolo, array $pending): void
    {
        FPersistentManager::accountUpdateAnagrafica(
            $userId,
            (string) $pending['nome'],
            (string) $pending['cognome'],
            (string) $pending['email']
        );
        self::aggiornaSessioneUtente($pending);

        if (!empty($pending['cambia_password']) && isset($pending['password_hash'])) {
            FPersistentManager::accountUpdatePasswordHash($userId, (string) $pending['password_hash']);
        }
        if ($ruolo === EPilota::$ruolo && is_array($pending['pilota'] ?? null)) {
            self::applicaProfiloPilota($userId, $pending['pilota']);
        }
    }

    /** allinea l'anagrafica in sessione ai nuovi dati */
    private static function aggiornaSessioneUtente(array $pending): void
    {
        CAuth::startSession();
        $_SESSION['user']['nome']    = (string) $pending['nome'];
        $_SESSION['user']['cognome'] = (string) $pending['cognome'];
        $_SESSION['user']['email']   = (string) $pending['email'];
    }

    private static function applicaProfiloPilota(int $userId, array $p): void
    {
        $attuale       = FPersistentManager::pilotaLoadByUtente($userId) ?? [];
        $categoriaPrec = (string) ($attuale['categoria'] ?? '');

        FPersistentManager::pilotaUpdateProfilo(
            $userId,
            (string) $p['categoria'],
            $p['licenza'] ?? null,
            $p['scadenza_licenza'] ?? null,
            self::datiFiscali($p)
        );

        // il cambio categoria richiede una nuova convalida dei documenti
        if ($categoriaPrec !== '' && $categoriaPrec !== (string) $p['categoria']) {
            self::richiediNuovaConvalida($userId);
        }
    }

    private static function datiFiscali(array $p): array
    {
        return [
            'codice_fiscale' => (string) ($p['codice_fiscale'] ?? ''),
            'indirizzo'      => (string) ($p['indirizzo'] ?? ''),
            'cap'            => (string) ($p['cap'] ?? ''),
            'comune'         => (string) ($p['comune'] ?? ''),
            'provincia'      => (string) ($p['provincia'] ?? ''),
            'fatt_indirizzo' => (string) ($p['fatt_indirizzo'] ?? ''),
            'fatt_cap'       => (string) ($p['fatt_cap'] ?? ''),
            'fatt_comune'    => (string) ($p['fatt_comune'] ?? ''),
            'fatt_provincia' => (string) ($p['fatt_provincia'] ?? ''),
        ];
    }

    private static function richiediNuovaConvalida(int $userId): void
    {
        FPersistentManager::pilotaUpdateDocumentiStato($userId, EPilota::DOC_IN_ATTESA);

        try {
            FPersistentManager::notificheNotificaAdminDocumentiPilota($userId);
        } catch (Throwable $e) {
            error_log('Notifica admin documenti pilota (cambio categoria): ' . $e->getMessage());
        }
    }
}
