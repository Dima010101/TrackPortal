<?php

// autenticazione e gestione sessione utente
class CAuth
{
    // rotte risolte da CFrontController
    public const ROUTES = [
        'GET accesso' => 'accedi',
        'POST accesso' => 'accedi',
        'GET registrazione' => 'registra',
        'POST registrazione' => 'registra',
        'POST logout' => 'esci',
        'GET verifica' => 'verificaCodice2FA',
        'POST verifica' => 'verificaCodice2FA',
        'POST rinvia-codice' => 'reinviaCodice2FA',
        'GET conferma-email/{p}' => 'confermaEmail',
        'POST rinvia-email' => 'reinviaConfermaEmail',
    ];

    // cache per richiesta dell'oggetto dominio
    private static ?EAccount $dominioCache = null;

    // avvia la sessione delegando al Foundation
    public static function startSession(): void
    {
        Session::start();
    }

    public static function isLogged(): bool
    {
        self::startSession();

        return !empty($_SESSION['user']['id']);
    }

    // utente correntemente loggato
    public static function utenteCorrente(): ?array
    {
        self::startSession();

        return $_SESSION['user'] ?? null;
    }

    public static function invalidaCacheDominio(): void
    {
        self::$dominioCache = null;
    }

    // oggetto dominio sotto-tipo di EAccount per la sessione corrente
    public static function utenteCorrenteDominio(): ?EAccount
    {
        self::startSession();
        if (empty($_SESSION['user']['id'])) {
            return null;
        }
        if (self::$dominioCache === null) {
            self::$dominioCache = FPersistentManager::accountFactoryDaId((int) $_SESSION['user']['id']);
        }

        return self::$dominioCache;
    }

    // forza il login, redirige a /auth/accesso se non loggato
    public static function richiediLogin(): void
    {
        if (!self::isLogged()) {
            self::startSession();
            $_SESSION['_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('warn', 'Devi accedere per visualizzare questa pagina.');
            redirect('/auth/accesso');
        }
    }

    // forza login e ruolo, mostra la pagina 403 se non ammesso
    public static function richiediRuolo(string|array $ruoli): void
    {
        self::richiediLogin();
        $dom      = self::utenteCorrenteDominio();
        $ammessi  = is_array($ruoli) ? $ruoli : [$ruoli];
        $ruoloEff = $dom !== null ? (get_class($dom))::$ruolo : '';
        if ($dom === null || !in_array($ruoloEff, $ammessi, true)) {
            VError::accessoNegato($ammessi);
            exit;
        }
    }

    // guard di ruolo che ritorna l'id dell'utente loggato
    public static function idUtenteConRuolo(string|array $ruoli): int
    {
        self::richiediRuolo($ruoli);

        return (int) self::utenteCorrente()['id'];
    }

    public static function disconnetti(): void
    {
        self::startSession();
        self::invalidaCacheDominio();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // GET mostra il form di login. POST verifica credenziali, gate email, avvio 2FA
    public function accedi(): void
    {
        self::startSession();
        if (self::isLogged()) {
            redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && self::processaLogin()) {
            return; // risposta gia' emessa, gate email non verificata
        }

        VAuth::login();
    }

    // GET mostra il form 2FA. POST verifica l'OTP e completa l'accesso
    public function verificaCodice2FA(): void
    {
        self::startSession();
        if (self::isLogged()) {
            redirect('/');
        }

        $pending = $_SESSION['_2fa'] ?? null;
        if (!is_array($pending) || empty($pending['user_id'])) {
            flash('warn', 'Sessione di verifica non valida o scaduta. Effettua di nuovo l\'accesso.');
            redirect('/auth/accesso');
        }

        $errors = $_SERVER['REQUEST_METHOD'] === 'POST' ? self::verificaOtp($pending) : [];
        VAuth::verifica2FA((string) ($pending['email'] ?? ''), $errors);
    }

    // POST rigenera e reinvia il codice 2FA
    public function reinviaCodice2FA(): void
    {
        self::startSession();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/auth/verifica');
        }

        $pending = $_SESSION['_2fa'] ?? null;
        if (!is_array($pending) || empty($pending['user_id'])) {
            flash('warn', 'Sessione di verifica non valida. Effettua di nuovo l\'accesso.');
            redirect('/auth/accesso');
        }
        if (!csrf_check(post('csrf_token'))) {
            flash('error', 'Token di sicurezza non valido.');
            redirect('/auth/verifica');
        }

        $destinatario = ['id' => $pending['user_id'], 'email' => $pending['email'] ?? '', 'nome' => $pending['nome'] ?? ''];
        FPersistentManager::notificheCodice2FA($destinatario, self::generaOtp($destinatario));
        flash('info', 'Ti abbiamo inviato un nuovo codice di verifica.');
        redirect('/auth/verifica');
    }

    // GET /auth/conferma-email/{token} conferma l'indirizzo email
    public function confermaEmail(int|string $token = ''): void
    {
        self::startSession();
        $token = trim((string) $token);
        $row   = $token !== '' ? FPersistentManager::accountLoadByTokenVerifica($token) : null;

        if ($row === null) {
            flash('error', 'Link di conferma non valido o già utilizzato.');
            redirect('/auth/accesso');
        }
        if (!empty($row['email_verificata'])) {
            flash('info', 'Indirizzo email già confermato. Puoi accedere.');
            redirect('/auth/accesso');
        }

        FPersistentManager::accountMarkEmailVerificata((int) $row['id']);
        flash('ok', 'Email confermata correttamente! Ora puoi accedere.');
        redirect('/auth/accesso');
    }

    // POST /auth/rinvia-email reinvia l'email di conferma registrazione
    public function reinviaConfermaEmail(): void
    {
        self::startSession();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/auth/accesso');
        }
        if (!csrf_check(post('csrf_token'))) {
            flash('error', 'Token di sicurezza non valido.');
            redirect('/auth/accesso');
        }

        $email = strtolower((string) post('email', ''));
        $row   = $email !== '' ? FPersistentManager::accountLoadByEmail($email) : null;
        // messaggio generico che non rivela se l'indirizzo e' registrato
        if ($row !== null && empty($row['email_verificata'])) {
            $token = bin2hex(random_bytes(32));
            FPersistentManager::accountSetTokenVerifica((int) $row['id'], $token);
            FPersistentManager::notificheConfermaRegistrazione($row, $token);
        }

        flash('info', 'Se l\'indirizzo è registrato e non ancora confermato, ti abbiamo inviato una nuova email di conferma.');
        redirect('/auth/accesso');
    }

    // logout via POST con controllo CSRF
    public function esci(): void
    {
        self::startSession();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }
        if (!csrf_check(post('csrf_token'))) {
            flash('error', 'Token di sicurezza non valido.');
            redirect('/');
        }

        self::disconnetti();
        flash('info', 'Sei stato disconnesso.');
        redirect('/');
    }

    // GET form di registrazione. POST crea l'utente con il sotto-tipo scelto
    public function registra(): void
    {
        self::startSession();
        if (self::isLogged()) {
            redirect('/');
        }

        $errors = [];
        $old    = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dati = self::datiRegistrazioneDaPost();
            // valori reinseriti nel form in caso di errore, mai le password
            $old    = array_diff_key($dati, array_flip(['password', 'password_conf']));
            $errors = self::validaRegistrazione($dati);
            if ($errors === []) {
                $errors = self::eseguiRegistrazione($dati);
            }
        }

        VAuth::registrazione($errors, $old);
    }

    // login e secondo fattore, helper privati

    // elabora il POST di login. Ritorna true se la risposta e' gia' emessa
    private static function processaLogin(): bool
    {
        if (!csrf_check(post('csrf_token'))) {
            flash('error', 'Token di sicurezza non valido. Riprova.');
            return false;
        }

        $email = strtolower((string) post('email', ''));
        $pass  = (string) post('password', '');
        if ($email === '' || $pass === '') {
            flash('error', 'Inserisci email e password.');
            return false;
        }

        $esito = self::verificaCredenziali($email, $pass);
        if (!$esito['ok']) {
            flash('error', $esito['error'] ?? 'Email o password non corretti.');
            return false;
        }

        // login bloccato finche' l'email non e' verificata
        if (empty($esito['row']['email_verificata'])) {
            VAuth::verificaEmailRichiesta((string) $esito['row']['email']);
            return true;
        }

        self::avvia2FA($esito['row']); // genera e invia il codice, poi reindirizza
    }

    // verifica credenziali e stato account senza aprire la sessione
    private static function verificaCredenziali(string $email, string $password): array
    {
        $row = FPersistentManager::accountRigaDopoVerificaPassword($email, $password);
        if (!$row) {
            return ['ok' => false, 'row' => null, 'error' => 'Email o password non corretti.'];
        }

        $blocchi = [
            'bannato'   => 'Account bannato. Contatta il supporto.',
            'sospeso'   => 'Account sospeso. Contatta il supporto.',
            'in_attesa' => 'La tua registrazione è in attesa di approvazione da parte di un amministratore.',
            'rifiutato' => 'La tua registrazione è stata respinta. Contatta il supporto per maggiori informazioni.',
        ];
        $errore = $blocchi[(string) $row['stato_account']] ?? null;

        return $errore === null
            ? ['ok' => true, 'row' => $row, 'error' => null]
            : ['ok' => false, 'row' => null, 'error' => $errore];
    }

    // apre la sessione utente dalla riga account, dopo il secondo fattore
    private static function completaLogin(array $row): bool
    {
        $dom = FPersistentManager::accountFactoryDaRigaUtente($row);
        if ($dom === null) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $cls = get_class($dom);
        $_SESSION['user'] = [
            'id'      => (int) $row['id'],
            'nome'    => $row['nome'],
            'cognome' => $row['cognome'],
            'email'   => $row['email'],
            'ruolo'   => $cls::$ruolo,
            'stato'   => $row['stato_account'],
        ];
        self::$dominioCache = $dom;

        return true;
    }

    // avvia il 2FA, o completa subito l'accesso se disattivato. Non ritorna mai
    private static function avvia2FA(array $row): never
    {
        $abilitato = (defined('AUTH_2FA_ENABLED') ? (bool) AUTH_2FA_ENABLED : true)
            && (defined('MAIL_ENABLED') ? (bool) MAIL_ENABLED : true);
        if (!$abilitato) {
            if (self::completaLogin($row)) {
                self::redirectDopoLogin();
            }
            flash('error', 'Profilo utente incompleto. Contatta il supporto.');
            redirect('/auth/accesso');
        }

        FPersistentManager::notificheCodice2FA($row, self::generaOtp($row));
        flash('info', 'Ti abbiamo inviato un codice di verifica via email per completare l\'accesso.');
        redirect('/auth/verifica');
    }

    // genera un OTP a 6 cifre e ricrea lo stato 2FA in sessione
    private static function generaOtp(array $destinatario): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        self::startSession();
        $_SESSION['_2fa'] = [
            'user_id'  => (int) $destinatario['id'],
            'hash'     => password_hash($code, PASSWORD_BCRYPT),
            'expires'  => time() + 600,
            'attempts' => 0,
            'email'    => (string) ($destinatario['email'] ?? ''),
            'nome'     => (string) ($destinatario['nome'] ?? ''),
        ];

        return $code;
    }

    // verifica l'OTP controllando scadenza, tentativi e hash
    private static function verificaOtp(array $pending): array
    {
        if (!csrf_check(post('csrf_token'))) {
            return ['Token di sicurezza non valido. Riprova.'];
        }
        if (time() > (int) $pending['expires']) {
            self::annulla2FA('Il codice di verifica è scaduto. Effettua di nuovo l\'accesso.');
        }
        if ((int) $pending['attempts'] >= 5) {
            self::annulla2FA('Troppi tentativi non riusciti. Effettua di nuovo l\'accesso.');
        }

        $code = preg_replace('/\D/', '', (string) post('codice', '')) ?? '';
        if ($code === '' || !password_verify($code, (string) $pending['hash'])) {
            $_SESSION['_2fa']['attempts'] = (int) $pending['attempts'] + 1;
            $rimasti = max(0, 5 - (int) $_SESSION['_2fa']['attempts']);

            return ['Codice non valido.' . ($rimasti > 0 ? ' Tentativi rimasti: ' . $rimasti . '.' : '')];
        }

        self::completaAccesso2FA((int) $pending['user_id']);
    }

    // codice corretto, apre la sessione utente e reindirizza. Non ritorna mai
    private static function completaAccesso2FA(int $userId): never
    {
        $row = FPersistentManager::accountLoadById($userId);
        if ($row === null || !self::completaLogin($row)) {
            self::annulla2FA('Impossibile completare l\'accesso. Riprova.');
        }

        unset($_SESSION['_2fa']);
        self::redirectDopoLogin();
    }

    // interrompe la verifica 2FA e rimanda al login con un messaggio
    private static function annulla2FA(string $messaggio): never
    {
        unset($_SESSION['_2fa']);
        flash('error', $messaggio);
        redirect('/auth/accesso');
    }

    // redirect post-login alla pagina d'origine o alla home, con benvenuto
    private static function redirectDopoLogin(): never
    {
        self::startSession();
        $next = $_SESSION['_after_login'] ?? '/';
        unset($_SESSION['_after_login']);
        flash('ok', 'Accesso effettuato. Benvenuto!');
        if (is_string($next) && str_contains($next, '/TrackPortal/')) {
            $next = substr($next, strpos($next, '/TrackPortal/') + strlen('/TrackPortal'));
        }

        redirect(is_string($next) && $next !== '' ? $next : '/');
    }

    // registrazione, helper privati

    // campi del form di registrazione
    private static function datiRegistrazioneDaPost(): array
    {
        return [
            'csrf_token' => (string) post('csrf_token', ''),
            'nome' => (string) post('nome', ''), 'cognome' => (string) post('cognome', ''),
            'email' => strtolower((string) post('email', '')),
            'password' => (string) post('password', ''), 'password_conf' => (string) post('password_conf', ''),
            'ruolo' => (string) post('ruolo', EPilota::$ruolo),
            'categoria' => (string) post('categoria', 'amatoriale'), 'licenza' => (string) post('licenza', ''),
            'scadenza_licenza' => (string) post('scadenza_licenza', ''),
            'codice_fiscale' => codice_fiscale_normalizza((string) post('codice_fiscale', '')),
            'nome_societa' => (string) post('nome_societa', ''), 'partita_iva' => (string) post('partita_iva', ''),
            // indirizzi del pilota, residenza e fatturazione entrambi obbligatori
            'indirizzo' => (string) post('indirizzo', ''), 'cap' => (string) post('cap', ''),
            'comune' => (string) post('comune', ''), 'provincia' => (string) post('provincia', ''),
            'fatt_indirizzo' => (string) post('fatt_indirizzo', ''), 'fatt_cap' => (string) post('fatt_cap', ''),
            'fatt_comune' => (string) post('fatt_comune', ''), 'fatt_provincia' => (string) post('fatt_provincia', ''),
        ];
    }

    private static function validaRegistrazione(array $d): array
    {
        $errors = array_merge(self::erroriCredenzialiRegistrazione($d), self::erroriRuoloRegistrazione($d));
        if ($d['ruolo'] === EPilota::$ruolo) {
            $errors = array_merge($errors, self::erroriPilotaRegistrazione($d), self::erroriDocumentiRegistrazione());
        }
        if ($errors === [] && FPersistentManager::existsByField(EAccount::class, 'email', $d['email'])) {
            $errors[] = 'Esiste gia un account con questa email.';
        }

        return $errors;
    }

    private static function erroriCredenzialiRegistrazione(array $d): array
    {
        $errors = [];
        if (!csrf_check($d['csrf_token'] !== '' ? $d['csrf_token'] : null)) {
            $errors[] = 'Token di sicurezza non valido. Riprova.';
        }
        if ($d['nome'] === '' || $d['cognome'] === '' || $d['email'] === '' || $d['password'] === '') {
            $errors[] = 'Compila tutti i campi obbligatori.';
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email non valida.';
        }
        if (strlen($d['password']) < 8) {
            $errors[] = 'La password deve essere di almeno 8 caratteri.';
        }
        if ($d['password'] !== $d['password_conf']) {
            $errors[] = 'Le password non coincidono.';
        }

        return $errors;
    }

    private static function erroriRuoloRegistrazione(array $d): array
    {
        $errors = [];
        $ruoliRegistrazione = [EPilota::$ruolo, EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo];
        if (!in_array($d['ruolo'], $ruoliRegistrazione, true)) {
            $errors[] = 'Ruolo non valido.';
        }
        if (in_array($d['ruolo'], [EGestoreCircuiti::$ruolo, EGestoreNoleggio::$ruolo], true)
            && ($d['nome_societa'] === '' || $d['partita_iva'] === '')) {
            $errors[] = 'Nome societa e Partita IVA sono obbligatori.';
        }

        return $errors;
    }

    // codice fiscale che intesta le fatture e indirizzi del pilota
    private static function erroriPilotaRegistrazione(array $d): array
    {
        $errors = [];
        if ($d['codice_fiscale'] === '') {
            $errors[] = 'Il codice fiscale è obbligatorio.';
        } elseif (!codice_fiscale_valido($d['codice_fiscale'])) {
            $errors[] = 'Codice fiscale non valido: usa il formato italiano di 16 caratteri.';
        }

        return array_merge(
            $errors,
            valida_blocco_indirizzo($d['indirizzo'], $d['cap'], $d['comune'], $d['provincia'], 'di residenza'),
            valida_blocco_indirizzo($d['fatt_indirizzo'], $d['fatt_cap'], $d['fatt_comune'], $d['fatt_provincia'], 'di fatturazione')
        );
    }

    // certificato medico e licenza o patente in PDF, obbligatori per il pilota
    private static function erroriDocumentiRegistrazione(): array
    {
        $errors = [];
        $docs   = [
            ['file' => $_FILES['certificato_medico'] ?? [], 'label' => 'il certificato medico'],
            ['file' => $_FILES['licenza_pdf'] ?? [],        'label' => 'la licenza/patente'],
        ];
        foreach ($docs as $doc) {
            if (FPersistentManager::documentoPilotaFileVuoto($doc['file'])) {
                $errors[] = 'Carica ' . $doc['label'] . ' in formato PDF.';
                continue;
            }
            try {
                FPersistentManager::documentoPilotaValidaUpload($doc['file']);
            } catch (Throwable $e) {
                $errors[] = ucfirst($doc['label']) . ': ' . $e->getMessage();
            }
        }

        return $errors;
    }

    // registra l'account in transazione, notifica e reindirizza al login
    private static function eseguiRegistrazione(array $d): array
    {
        // token monouso per il link di conferma email
        $token = bin2hex(random_bytes(32));
        try {
            FPersistentManager::transaction(function () use ($d, $token): void {
                self::persistiNuovoAccount($d, $token);
            });
        } catch (Throwable $ex) {
            return ['Errore durante la registrazione: ' . $ex->getMessage()];
        }

        self::notificheRegistrazione($d, $token);
        flash('ok', $d['ruolo'] === EPilota::$ruolo
            ? 'Registrazione completata! Ti abbiamo inviato un\'email di conferma: verifica il tuo indirizzo per poter accedere. I documenti verranno poi convalidati da un amministratore prima di poterti prenotare alle sessioni.'
            : 'Registrazione completata! Ti abbiamo inviato un\'email di conferma: verifica il tuo indirizzo per poter accedere. L\'affiliazione verrà approvata da un amministratore.');
        redirect('/auth/accesso');
    }

    private static function persistiNuovoAccount(array $d, string $token): void
    {
        $entity = self::nuovaEntitaAccount($d);
        // email non ancora confermata e token del link di verifica
        $entity->setEmailVerificata(false);
        $entity->setTokenVerifica($token);
        FPersistentManager::store($entity);

        // documenti pilota salvati dopo lo store perche' serve l'id
        if ($entity instanceof EPilota) {
            self::salvaDocumentiPilota($entity);
        }
    }

    private static function nuovaEntitaAccount(array $d): EAccount
    {
        $hash      = password_hash($d['password'], PASSWORD_BCRYPT);
        $creazione = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($d['ruolo'] === EGestoreCircuiti::$ruolo) {
            return new EGestoreCircuiti(
                $d['nome'], $d['cognome'], $d['email'], $hash, 'attivo',
                null, $creazione, $d['nome_societa'], $d['partita_iva'], 'in_attesa'
            );
        }
        if ($d['ruolo'] === EGestoreNoleggio::$ruolo) {
            return new EGestoreNoleggio(
                $d['nome'], $d['cognome'], $d['email'], $hash, 'attivo',
                null, $creazione, $d['nome_societa'], $d['partita_iva'], 'in_attesa'
            );
        }

        return self::nuovoPilota($d, $hash, $creazione);
    }

    // il pilota accede subito ma i documenti restano in attesa di convalida admin
    private static function nuovoPilota(array $d, string $hash, string $creazione): EPilota
    {
        $pilota = new EPilota(
            $d['nome'], $d['cognome'], $d['email'], $hash, 'attivo',
            null, $creazione,
            $d['categoria'], $d['licenza'] ?: null, $d['scadenza_licenza'] ?: null
        );
        $pilota->setCodiceFiscale($d['codice_fiscale']);
        $pilota->setIndirizzo(trim($d['indirizzo']));
        $pilota->setCap(trim($d['cap']));
        $pilota->setComune(trim($d['comune']));
        $pilota->setProvincia(provincia_normalizza($d['provincia']));
        $pilota->setFattIndirizzo(trim($d['fatt_indirizzo']));
        $pilota->setFattCap(trim($d['fatt_cap']));
        $pilota->setFattComune(trim($d['fatt_comune']));
        $pilota->setFattProvincia(provincia_normalizza($d['fatt_provincia']));

        return $pilota;
    }

    private static function salvaDocumentiPilota(EPilota $entity): void
    {
        $pid = (int) $entity->getId();
        $entity->setCertificatoMedicoPath(
            FPersistentManager::documentoPilotaSalva($pid, 'certificato_medico', $_FILES['certificato_medico'] ?? [])
        );
        $entity->setLicenzaPath(
            FPersistentManager::documentoPilotaSalva($pid, 'licenza', $_FILES['licenza_pdf'] ?? [])
        );
        FPersistentManager::flush();
    }

    // notifiche best effort di conferma all'utente e avviso agli admin
    private static function notificheRegistrazione(array $d, string $token): void
    {
        try {
            $account = FPersistentManager::accountLoadByEmail($d['email']);
            if ($account === null) {
                return;
            }
            FPersistentManager::notificheConfermaRegistrazione($account, $token);
            if ($d['ruolo'] === EPilota::$ruolo) {
                FPersistentManager::notificheNotificaAdminDocumentiPilota((int) $account['id']);
            } else {
                FPersistentManager::notificheNotificaAdminAffiliazione(
                    $account + ['nome_societa' => $d['nome_societa'], 'partita_iva' => $d['partita_iva']],
                    ruolo_label($d['ruolo'])
                );
            }
        } catch (Throwable $e) {
            error_log('Notifiche registrazione: ' . $e->getMessage());
        }
    }
}
