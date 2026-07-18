<?php

/*metodi di assistenza a tutte le classi */

/**
 *restituisce l'url inter di un path, serve per i link nelle email 
 */
function absolute_url(string $path = ''): string
{
    $root = defined('APP_URL') ? rtrim((string) constant('APP_URL'), '/') : '';
    if ($root === '') {
        return url($path);
    }

    // APP_URL include già la base path (/TrackPortal): evita di duplicarla.
    $base = rtrim((string) constant('APP_BASE_URL'), '/');
    $rel  = '/' . ltrim($path, '/');
    if ($base !== '' && ($rel === $base || str_starts_with($rel, $base . '/'))) {
        $rel = substr($rel, strlen($base));
        $rel = '/' . ltrim($rel, '/');
    }

    return $root . $rel;
}

/** Costruisce un URL assoluto rispetto alla base path dell'app. */
function url(string $path = ''): string
{
    $base = rtrim((string) constant('APP_BASE_URL'), '/');
    $path = '/' . ltrim($path, '/');
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        return $path;
    }

    return $base . $path;
}

/** metodi per cambiare formato alla data */
function date_pretty(string $sql): string
{
    if ($sql === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($sql))->format('d/m/Y H:i');
    } catch (Exception) {
        return '';
    }
}

function date_short(string $sql): string
{
    if ($sql === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($sql))->format('d/m/Y');
    } catch (Exception) {
        return '';
    }
}

/** Formatta importo numerico in valuta. */
function money(float $value, string $currency = 'EUR'): string
{
    if (extension_loaded('intl')) {
        $locale = match ($currency) {
            'USD' => 'en_US',
            'GBP' => 'en_GB',
            default => 'it_IT',
        };
        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $out = $fmt->formatCurrency($value, $currency);
        if ($out !== false) {
            return $out;
        }
    }

    $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF ', 'JPY' => '¥'];
    $sym = $symbols[$currency] ?? $currency . ' ';
    $decimali = $currency === 'JPY' ? 0 : 2;
    return $sym . number_format($value, $decimali, ',', '.');
}

/** impone la prima lettera maiuscola per migliore visualizzazione */
function tp_ucfirst(?string $s): string
{
    if ($s === null || $s === '') {
        return '';
    }
    $s = (string) $s;

    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}


/** normalizzazione della targa in maiuscolo e senza spazi */
function targa_normalizza(string $targa): string
{
    return strtoupper(preg_replace('/\s+/', '', $targa) ?? '');
}

/** Valida il formato della targa(considerata italiana)*/
function targa_valida(string $targa): bool
{
    $t = targa_normalizza($targa);

    return preg_match('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/', $t) === 1
        || preg_match('/^[A-Z]{2}[0-9]{5}$/', $t) === 1;
}

/** POST sanitizzato come stringa*/
function post(string $key, ?string $default = null): ?string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}


/** GET sanitizzato come stringa */
function get(string $key, ?string $default = null): ?string
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

/** Aggiunge un flash message in sessione*/
function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        CAuth::startSession();
    }
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Reindirizza ad un URL relativo all'app e termina lo script. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Verifica un token CSRF inviato. */
function csrf_check(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

/** Rende il campo input nascosto per il token CSRF */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Genera/recupera token CSRF della sessione corrente. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**verifica che il nome del titolare della carta di credito sia valido*/
function cc_titolare_valido(string $valore): bool
{
    $v = trim($valore);
    $len = mb_strlen($v);

    return $len >= 2 && $len <= 80
        && preg_match("/^[\p{L}][\p{L}\p{M}'’.\- ]*$/u", $v) === 1;
}

/**Serializzazione JSON con gestione errori; imposta Content-Type (endpoint API).*/
function json_print(mixed $value, int $httpStatus = 200, int $extraFlags = 0): void
{
    if ($httpStatus !== 200) {
        http_response_code($httpStatus);
    }
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | $extraFlags);
    } catch (JsonException) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8', true);
        echo '{"error":"Impossibile serializzare la risposta JSON"}';
    }
}

/** verifica che numero carta è valido: 13–19 cifre e checksum di Luhn corretta. */
function cc_numero_valido(string $numero): bool
{
    $cifre = cc_solo_cifre($numero);
    $len = strlen($cifre);

    return $len >= 13 && $len <= 19 && cc_luhn_valido($cifre);
}

/** Estrae le sole cifre da un numero carta (rimuove spazi e separatori) */
function cc_solo_cifre(string $valore): string
{
    return preg_replace('/\D/', '', $valore) ?? '';
}

/** Algoritmo di Luhn (checksum standard delle carte di pagamento)*/
function cc_luhn_valido(string $cifre): bool
{
    if ($cifre === '' || !ctype_digit($cifre)) {
        return false;
    }
    $somma = 0;
    $alt = false;
    for ($i = strlen($cifre) - 1; $i >= 0; $i--) {
        $d = (int) $cifre[$i];
        if ($alt) {
            $d *= 2;
            if ($d > 9) {
                $d -= 9;
            }
        }
        $somma += $d;
        $alt = !$alt;
    }

    return $somma % 10 === 0;
}

/**
 * Scadenza nel formato MM/YYYY con mese valido e carta non scaduta (entro ~20 anni dalla data odierna)*/
function cc_scadenza_valida(string $scad): bool
{
    if (preg_match('#^(\d{2})/(\d{4})$#', trim($scad), $m) !== 1) {
        return false;
    }
    $mese = (int) $m[1];
    $anno = (int) $m[2];
    if ($mese < 1 || $mese > 12) {
        return false;
    }
    $annoCorr = (int) date('Y');
    $meseCorr = (int) date('n');
    if ($anno < $annoCorr || ($anno === $annoCorr && $mese < $meseCorr)) {
        return false;
    }

    return $anno <= $annoCorr + 20;
}

/** CVV: 3 o 4 cifre */
function cc_cvv_valido(string $cvv): bool
{
    return preg_match('/^\d{3,4}$/', trim($cvv)) === 1;
}

/**
 * restituisce True se il browser ha rispedito il cookie di sessione (per vedere se sono abilitati)  */
function cookie_sessione_presente(): bool
{
    $nome = defined('SESSION_NAME') ? (string) constant('SESSION_NAME') : session_name();

    return $nome !== '' && !empty($_COOKIE[$nome]);
}

/** normalizza un codice fiscale: maiuscolo, senza spazi. */
function codice_fiscale_normalizza(string $cf): string
{
    return strtoupper(preg_replace('/\s+/', '', $cf) ?? '');
}

/** valida il formato del codice fiscale italiano */
function codice_fiscale_valido(string $cf): bool
{
    $v = codice_fiscale_normalizza($cf);

    return preg_match(
        '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/',
        $v
    ) === 1;
}

/** Valida indirizzo (via, CAP, comune, provincia) i cui campi sono
 * tutti obbligatori. Restituisce la lista degli errori (vuota se valido).
 */
function valida_blocco_indirizzo(
    string $indirizzo,
    string $cap,
    string $comune,
    string $provincia,
    string $etichetta
): array {
    $errors = [];

    $via = trim($indirizzo);
    if ($via === '') {
        $errors[] = "L'indirizzo {$etichetta} è obbligatorio.";
    } elseif (mb_strlen($via) > 255) {
        $errors[] = "L'indirizzo {$etichetta} non può superare 255 caratteri.";
    }

    $c = trim($cap);
    if ($c === '') {
        $errors[] = "Il CAP {$etichetta} è obbligatorio.";
    } elseif (!preg_match('/^\d{5}$/', $c)) {
        $errors[] = "Il CAP {$etichetta} deve essere di 5 cifre.";
    }

    $com = trim($comune);
    if ($com === '') {
        $errors[] = "Il comune {$etichetta} è obbligatorio.";
    } elseif (mb_strlen($com) > 120) {
        $errors[] = "Il comune {$etichetta} non può superare 120 caratteri.";
    }

    $pr = trim($provincia);
    if ($pr === '') {
        $errors[] = "La provincia {$etichetta} è obbligatoria.";
    } elseif (!preg_match('/^[A-Za-z]{2}$/', $pr)) {
        $errors[] = "La provincia {$etichetta} deve essere la sigla di 2 lettere (es. MI).";
    }

    return $errors;
}

/** Normalizza la sigla provincia in maiuscolo e senza spazi */
function provincia_normalizza(string $prov): string
{
    return strtoupper(trim($prov));
}



/**METODI DI HELPERS USATI PER PRESENTATION E TEMPLATES */

/** Markup <img> del logo del sito. */
function logo_img(string $class = 'tp-logo', string $alt = ''): string
{
    if ($alt === '') {
        $name = defined('APP_NAME') ? constant('APP_NAME') : 'TrackPortal';
        $alt = is_string($name) && $name !== '' ? $name : 'TrackPortal';
    }

    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';

    return '<img' . $classAttr
        . ' src="' . e(logo_url()) . '"'
        . ' alt="' . e($alt) . '"'
        . ' width="22" height="14" decoding="async">';
}

/** URL pubblico del logo */
function logo_url(): string
{
    $path = logo_path();
    $v = is_file($path) ? (int) filemtime($path) : time();

    return url('/public/img/logo.svg') . '?v=' . $v;
}


/** Dati del logo per creazione PDF */
function logo_data_uri(): string
{
    $path = logo_path();
    if (!is_file($path)) {
        return '';
    }

    $svg = file_get_contents($path);
    if ($svg === false || $svg === '') {
        return '';
    }

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/** definizione percorso assoluto del logo del sito */
function logo_path(): string
{
    return TRACKPORTAL_BASE_DIR . '/public/img/logo.svg';
}

/**
 * Restituisce le icone rispetto al mapping interno */
function icon(string $name, string $class = '', int $size = 16): string
{
    $paths = [
        'home'        => '<path d="M3 12 12 3l9 9"/><path d="M5 10v10h14V10"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'flag'        => '<path d="M4 21V4"/><path d="M4 4h13l-2 4 2 4H4"/>',
        'calendar'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'history'     => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'login'       => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'check'       => '<path d="m20 6-11 11L4 12"/>',
        'alert'       => '<path d="M12 9v4M12 17h0"/><path d="M10.3 3.7 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0z"/>',
        'info'        => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h0"/>',
        'x'           => '<path d="M18 6 6 18M6 6l12 12"/>',
        'edit'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>',
        'trash'       => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'car'         => '<path d="M5 17h14M5 17v-5l2-5h10l2 5v5M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/>',
        'tools'       => '<path d="M14.7 6.3a4 4 0 1 1-1 7.4l-7.4 7.4-2.4-2.4 7.4-7.4a4 4 0 0 1 3.4-5z"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'ticket'      => '<path d="M3 7v3a2 2 0 1 1 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 1 1 0-4V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 11v2M13 17v2"/>',
        'receipt'     => '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2V2"/><path d="M8 7h8M8 11h8M8 15h6"/>',
        'building'    => '<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22V12h6v10M9 6h2M13 6h2M9 10h2M13 10h2"/>',
        'dashboard'   => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
        'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'arrow-left'  => '<path d="m15 18-6-6 6-6"/>',
        'arrow-right' => '<path d="m9 18 6-6-6-6"/>',
        'arrow-down'  => '<path d="m6 9 6 6 6-6"/>',
        'arrow-up'    => '<path d="m6 15 6-6 6 6"/>',
        'gauge'       => '<path d="M12 14l4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'tag'         => '<path d="m20.6 13.4-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'lock'        => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'circuit'     => '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18M3 12h18"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'trophy'      => '<path d="M7 4h10v4a5 5 0 0 1-10 0V4z"/><path d="M7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3"/><path d="M9 16h6M10 20h4M12 13v3"/>',
        'users'       => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M21 20c0-2.7-1.6-5-4-5.7"/>',
        'wallet'      => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14" r="1.2"/>',
        'trending'    => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
    ];

    $svg = $paths[$name] ?? '<circle cx="12" cy="12" r="9"/>';
    $cls = $class ? ' class="' . e($class) . '"' : '';
    return sprintf(
        '<svg%s width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
        $cls, $size, $size, $svg
    );
}

/**
 * Classe CSS attiva per i link di navigazione.
 * Confronta lo "script logico" corrente (URL) col path indicato.
 * Funziona col front controller: il routing usa REQUEST_URI, non SCRIPT_NAME.
 */
function nav_active(string $matchPath): string
{
    $current = (string)($_SERVER['REQUEST_URI'] ?? '');
    $pathOnly = explode('?', $current, 2)[0];
    $match = url($matchPath);
    if ($pathOnly === $match || str_ends_with($pathOnly, $matchPath)) {
        return 'is-active';
    }
    if (str_starts_with($pathOnly, $match . '/')) {
        return 'is-active';
    }
    return '';
}

/** converte caratteri con significato speciale in maniera comprensibile da HTML */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Etichetta ben formattata per i ruoli */
function ruolo_label(string $ruolo): string
{
    return match ($ruolo) {
        'pilota'               => 'Pilota',
        'gestore_circuiti'     => 'Gestore di circuiti',
        'gestore_noleggio'     => 'Azienda di noleggio',
        'admin'                => 'Amministratore',
        default    => ucfirst($ruolo),
    };
}

/** mapping Tipologia-icona per gli alert */
function flash_icon(string $type): string
{
    return match ($type) {
        'error' => icon('alert'),
        'warn'  => icon('alert'),
        'info'  => icon('info'),
        'ok'    => icon('check'),
        default => icon('info'),
    };
}

/** rende l'importo formattato nella valuta nativa e lo avvolge in uno <span class="tp-money"> 
 * con i dati grezzi, così che lo script di front-end possa riconvertirlo nella valuta scelta dall'utente
 * L'importo registrato/fatturato resta invariato nella valuta originale
 */
function prezzo_convertibile(float $value, string $currency = 'EUR'): string
{
    $currency = strtoupper($currency) ?: 'EUR';

    return sprintf(
        '<span class="tp-money" data-amount="%s" data-base="%s">%s</span>',
        number_format($value, 2, '.', ''),
        e($currency),
        money($value, $currency)
    );
}

/**
 * Selettore di valuta per le schermate di prenotazione. Il valore scelto è
 * salvato lato client (localStorage) e i tassi arrivano dall'endpoint /api/valute.
 */
function valuta_switcher(string $class = ''): string
{
    $options = '';
    foreach (valute_supportate() as $code => $label) {
        $options .= sprintf(
            '<option value="%s">%s — %s</option>',
            e($code),
            e($code),
            e($label)
        );
    }

    $cls = trim('tp-valuta ' . $class);

    return sprintf(
        '<div class="%s">'
        . '<label class="tp-valuta-label" for="tp-valuta-select">%s Visualizza prezzi in</label>'
        . '<div class="tp-valuta-control">'
        . '<select id="tp-valuta-select" class="tp-valuta-select" data-tp-valuta data-endpoint="%s" '
        . 'aria-label="Valuta di visualizzazione dei prezzi">%s</select>'
        . '</div></div>',
        e($cls),
        icon('wallet', 'tp-valuta-icon'),
        e(url('/api/valute')),
        $options
    );
}

/**mapping valute selezionabili nelle schermate di prenotazione */
function valute_supportate(): array
{
    return [
        'EUR' => 'Euro',
        'USD' => 'Dollaro USA',
        'GBP' => 'Sterlina',
        'CHF' => 'Franco svizzero',
        'JPY' => 'Yen giapponese',
    ];
}

/** Etichetta ben formattata per gli stati di prenotazione. */
function stato_label(string $stato): string
{
    return match ($stato) {
        'confermata' => 'Confermata',
        'in_attesa'  => 'In attesa',
        'cancellata' => 'Annullata',
        'annullata'  => 'Annullata',
        'terminata'  => 'Terminata',
        default      => ucfirst($stato),
    };
}

/** effettua conversione di un orario da "HH:MM:SS" a "HH:MM". */
function tp_time_hm(?string $t): string
{
    if ($t === null || $t === '') {
        return '';
    }

    return substr((string) $t, 0, 5);
}

/**
 *  Istogramma.
 *  Chiavi supportate:
 *   - bar_label : etichetta legenda/asse sinistro (default 'Prenotazioni')
 *   - line_label : etichetta legenda/asse destro (default 'Ricavi')
 *   - line_currency : se valorizzata, linea e asse destro come importo
 *   - empty_text : messaggio mostrato quando la serie è vuota o tutta a zero
 */
function combo_chart(array $serie, array $opts = []): string
{
    $barLabel   = (string) ($opts['bar_label'] ?? 'Prenotazioni');
    $lineLabel  = (string) ($opts['line_label'] ?? 'Ricavi');
    $currency   = isset($opts['line_currency']) && $opts['line_currency'] !== ''
        ? (string) $opts['line_currency'] : null;
    $emptyText  = (string) ($opts['empty_text'] ?? 'Nessun dato disponibile per il periodo.');
    // Posizione dell'etichetta valore delle barre: 'above' (sopra la barra) o
    // 'below' (sotto l'asse, tra colonne ed etichetta del mese).
    $valueBelow = (string) ($opts['bar_value_pos'] ?? 'above') === 'below';
    // Titoli degli assi in alto a sinistra/destra. Se disattivati, l'unica
    // legenda resta quella centrata sotto al grafico.
    $showAxisTitles = (bool) ($opts['axis_titles'] ?? true);
    // Etichette numeriche del valore sopra/sotto ogni barra. Se disattivate, le
    // barre restano "pulite" e i valori restano leggibili solo dal tooltip.
    $showBarValues  = (bool) ($opts['bar_values'] ?? true);

    $n = count($serie);
    $maxBar = 0.0;
    $maxLine = 0.0;
    foreach ($serie as $row) {
        $maxBar  = max($maxBar, (float) ($row['bar'] ?? 0));
        $maxLine = max($maxLine, (float) ($row['line'] ?? 0));
    }

    if ($n === 0 || ($maxBar <= 0 && $maxLine <= 0)) {
        return '<div class="tp-combo-empty">' . icon('trending', '', 40)
            . '<p>' . e($emptyText) . '</p></div>';
    }

    //  Geometria grafico: dimensioni, padding, coordinate di disegno
    $W = 760.0; $H = $valueBelow ? 316.0 : 300.0;
    $padL = 46.0; $padR = 58.0; $padT = $showAxisTitles ? 36.0 : 18.0; $padB = $valueBelow ? 46.0 : 30.0;
    $x0 = $padL; $x1 = $W - $padR;
    $y0 = $padT; $y1 = $H - $padB;
    $plotW = $x1 - $x0; $plotH = $y1 - $y0;

    $div = 4;
    $stepBar  = tp_chart_nice_step($maxBar, $div);
    $stepLine = tp_chart_nice_step($maxLine, $div);
    $niceBar  = $stepBar * $div;
    $niceLine = $stepLine * $div;

    $slotW = $plotW / $n;
    $barW  = min($slotW * 0.46, 42.0);

    $uid = substr(md5(uniqid('cc', true)), 0, 8);
    $gradBars = 'tpccBars' . $uid;
    $gradArea = 'tpccArea' . $uid;

    $centerX = static fn (int $i): float => $x0 + $slotW * ($i + 0.5);
    $barY    = static fn (float $v): float => $y1 - ($v / $niceBar) * $plotH;
    $lineY   = static fn (float $v): float => $y1 - ($v / $niceLine) * $plotH;

    // Griglia orizzontale + tacche dei due assi 
    $grid = ''; $axisL = ''; $axisR = '';
    for ($k = 0; $k <= $div; $k++) {
        $y = $y1 - ($plotH * $k / $div);
        $grid .= sprintf(
            '<line class="tp-cc-grid" x1="%s" y1="%s" x2="%s" y2="%s"/>',
            tp_svg_num($x0), tp_svg_num($y), tp_svg_num($x1), tp_svg_num($y)
        );
        $axisL .= sprintf(
            '<text class="tp-cc-tick tp-cc-tick-left" x="%s" y="%s">%s</text>',
            tp_svg_num($x0 - 8), tp_svg_num($y + 3.5),
            e(number_format(round($stepBar * $k), 0, ',', '.'))
        );
        $valR = $stepLine * $k;
        $axisR .= sprintf(
            '<text class="tp-cc-tick tp-cc-tick-right" x="%s" y="%s">%s</text>',
            tp_svg_num($x1 + 8), tp_svg_num($y + 3.5),
            e($currency !== null ? tp_money_compact($valR, $currency) : number_format(round($valR), 0, ',', '.'))
        );
    }

    // Barre (rettangoli con sommità arrotondata) 
    $bars = ''; $valsBar = ''; $months = '';
    foreach ($serie as $i => $row) {
        $v  = (float) ($row['bar'] ?? 0);
        $cx = $centerX($i);
        $bx = $cx - $barW / 2;
        $by = $barY($v);
        $bh = $y1 - $by;
        $r  = min(5.0, $barW / 2, $bh);
        if ($bh > 0.5) {
            $bars .= '<path class="tp-cc-bar" fill="url(#' . $gradBars . ')" d="'
                . 'M' . tp_svg_num($bx) . ',' . tp_svg_num($y1)
                . ' L' . tp_svg_num($bx) . ',' . tp_svg_num($by + $r)
                . ' Q' . tp_svg_num($bx) . ',' . tp_svg_num($by) . ' ' . tp_svg_num($bx + $r) . ',' . tp_svg_num($by)
                . ' L' . tp_svg_num($bx + $barW - $r) . ',' . tp_svg_num($by)
                . ' Q' . tp_svg_num($bx + $barW) . ',' . tp_svg_num($by) . ' ' . tp_svg_num($bx + $barW) . ',' . tp_svg_num($by + $r)
                . ' L' . tp_svg_num($bx + $barW) . ',' . tp_svg_num($y1) . ' Z"/>';
        }
        if ($showBarValues && $v > 0) {
            $valsBar .= sprintf(
                '<text class="tp-cc-val-bar%s" x="%s" y="%s">%s</text>',
                $valueBelow ? ' tp-cc-val-bar-below' : '',
                tp_svg_num($cx), tp_svg_num($valueBelow ? $y1 + 17 : $by - 7),
                e(number_format($v, 0, ',', '.'))
            );
        }
        $months .= sprintf(
            '<text class="tp-cc-month" x="%s" y="%s">%s</text>',
            tp_svg_num($cx), tp_svg_num($valueBelow ? $y1 + 33 : $y1 + 18),
            e((string) ($row['label'] ?? ''))
        );
    }

    // Area + linea + punti + etichette ricavi 
    $pts = [];
    foreach ($serie as $i => $row) {
        $pts[] = [$centerX($i), $lineY((float) ($row['line'] ?? 0))];
    }
    $polyline = implode(' ', array_map(
        static fn (array $p): string => tp_svg_num($p[0]) . ',' . tp_svg_num($p[1]),
        $pts
    ));
    $area = 'M' . tp_svg_num($pts[0][0]) . ',' . tp_svg_num($y1);
    foreach ($pts as $p) {
        $area .= ' L' . tp_svg_num($p[0]) . ',' . tp_svg_num($p[1]);
    }
    $area .= ' L' . tp_svg_num($pts[$n - 1][0]) . ',' . tp_svg_num($y1) . ' Z';

    $dots = '';
    foreach ($pts as [$px, $py]) {
        $dots .= sprintf(
            '<circle class="tp-cc-dot" cx="%s" cy="%s" r="3.6"/>',
            tp_svg_num($px), tp_svg_num($py)
        );
    }

    //  bottoni interattivi: una colonna per mese.
    // Hover in css
    $tipW = 150.0;
    $tipH = 64.0;
    $cols = '';
    foreach ($serie as $i => $row) {
        $bv = (float) ($row['bar'] ?? 0);
        $lv = (float) ($row['line'] ?? 0);
        [$px, $py] = $pts[$i];
        $cx    = $centerX($i);
        $slotX = $x0 + $slotW * $i;

        // Tooltip ancorato sopra il punto, ma sempre dentro al viewBox.
        $tx = max(2.0, min($cx - $tipW / 2, $W - $tipW - 2.0));
        $ty = max(2.0, min($py - $tipH - 12.0, $y1 - $tipH));

        $monthTxt = e((string) ($row['label'] ?? ''));
        $barTxt   = e($barLabel . ': ' . number_format($bv, 0, ',', '.'));
        $lineTxt  = e($lineLabel . ': ' . ($currency !== null
            ? tp_money_compact($lv, $currency)
            : number_format($lv, 0, ',', '.')));

        $tip = '<g class="tp-cc-tip">'
            . '<rect class="tp-cc-tip-bg" x="' . tp_svg_num($tx) . '" y="' . tp_svg_num($ty) . '" width="' . tp_svg_num($tipW) . '" height="' . tp_svg_num($tipH) . '" rx="8"/>'
            . '<text class="tp-cc-tip-title" x="' . tp_svg_num($tx + 12) . '" y="' . tp_svg_num($ty + 20) . '">' . $monthTxt . '</text>'
            . '<circle class="tp-cc-tip-key tp-cc-tip-key-bar" cx="' . tp_svg_num($tx + 14) . '" cy="' . tp_svg_num($ty + 36) . '" r="4"/>'
            . '<text class="tp-cc-tip-row" x="' . tp_svg_num($tx + 24) . '" y="' . tp_svg_num($ty + 39.5) . '">' . $barTxt . '</text>'
            . '<circle class="tp-cc-tip-key tp-cc-tip-key-line" cx="' . tp_svg_num($tx + 14) . '" cy="' . tp_svg_num($ty + 52) . '" r="4"/>'
            . '<text class="tp-cc-tip-row" x="' . tp_svg_num($tx + 24) . '" y="' . tp_svg_num($ty + 55.5) . '">' . $lineTxt . '</text>'
            . '</g>';

        $cols .= '<g class="tp-cc-col">'
            . '<title>' . $monthTxt . ' · ' . $barTxt . ' · ' . $lineTxt . '</title>'
            . '<rect class="tp-cc-band" x="' . tp_svg_num($slotX) . '" y="' . tp_svg_num($y0) . '" width="' . tp_svg_num($slotW) . '" height="' . tp_svg_num($plotH) . '"/>'
            . '<line class="tp-cc-guide" x1="' . tp_svg_num($cx) . '" y1="' . tp_svg_num($y0) . '" x2="' . tp_svg_num($cx) . '" y2="' . tp_svg_num($y1) . '"/>'
            . '<circle class="tp-cc-dot-halo" cx="' . tp_svg_num($px) . '" cy="' . tp_svg_num($py) . '" r="8"/>'
            . '<circle class="tp-cc-dot-hi" cx="' . tp_svg_num($px) . '" cy="' . tp_svg_num($py) . '" r="4.4"/>'
            . $tip
            . '<rect class="tp-cc-hit" x="' . tp_svg_num($slotX) . '" y="' . tp_svg_num($y0) . '" width="' . tp_svg_num($slotW) . '" height="' . tp_svg_num($plotH) . '"/>'
            . '</g>';
    }

    $simboli = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF', 'JPY' => '¥'];
    $axisTitleR = $lineLabel
        . ($currency !== null ? ' (' . ($simboli[strtoupper($currency)] ?? strtoupper($currency)) . ')' : '');

    $svg = '<svg class="tp-cc-svg" viewBox="0 0 ' . tp_svg_num($W) . ' ' . tp_svg_num($H) . '" '
        . 'preserveAspectRatio="xMidYMid meet" role="img" '
        . 'aria-label="' . e($barLabel . ' e ' . $lineLabel) . '">'
        . '<defs>'
        . '<linearGradient id="' . $gradBars . '" x1="0" y1="0" x2="0" y2="1">'
        . '<stop class="tp-cc-grad-bar-top" offset="0"/><stop class="tp-cc-grad-bar-bottom" offset="1"/>'
        . '</linearGradient>'
        . '<linearGradient id="' . $gradArea . '" x1="0" y1="0" x2="0" y2="1">'
        . '<stop class="tp-cc-grad-area-top" offset="0"/><stop class="tp-cc-grad-area-bottom" offset="1"/>'
        . '</linearGradient>'
        . '</defs>'
        . ($showAxisTitles
            ? '<text class="tp-cc-axis-title tp-cc-axis-title-left" x="' . tp_svg_num($x0 - 8) . '" y="20">' . e($barLabel) . '</text>'
                . '<text class="tp-cc-axis-title tp-cc-axis-title-right" x="' . tp_svg_num($x1 + 8) . '" y="20">' . e($axisTitleR) . '</text>'
            : '')
        . '<g>' . $grid . '</g>'
        . '<g>' . $axisL . $axisR . '</g>'
        . '<g>' . $bars . '</g>'
        . '<path class="tp-cc-area" fill="url(#' . $gradArea . ')" d="' . $area . '"/>'
        . '<polyline class="tp-cc-line" points="' . $polyline . '"/>'
        . '<g>' . $dots . '</g>'
        . '<g>' . $months . '</g>'
        . '<g>' . $valsBar . '</g>'
        . '<g class="tp-cc-cols">' . $cols . '</g>'
        . '</svg>';

    $legend = '<div class="tp-cc-legend">'
        . '<span class="tp-cc-legend-item"><span class="tp-cc-legend-swatch tp-cc-legend-bar"></span>' . e($barLabel) . '</span>'
        . '<span class="tp-cc-legend-item"><span class="tp-cc-legend-swatch tp-cc-legend-line"></span>' . e($lineLabel) . '</span>'
        . '</div>';

    return '<figure class="tp-combo-chart">' . $svg . $legend . '</figure>';
}

/** Garantisce assi con tacche leggibili.
 * Passo (1/2/2.5/5/10 × 10^n) tale che passo × $divisioni copra $max.
 */
function tp_chart_nice_step(float $max, int $divisioni): float
{
    $max = max($max, 1.0);
    $divisioni = max(1, $divisioni);
    $rough = $max / $divisioni;
    $exp = (int) floor(log10($rough));
    $base = 10 ** $exp;
    $frac = $rough / $base;
    $nf = match (true) {
        $frac <= 1.0 => 1.0,
        $frac <= 2.0 => 2.0,
        $frac <= 2.5 => 2.5,
        $frac <= 5.0 => 5.0,
        default      => 10.0,
    };

    return $nf * $base;
}

/** formatta coordinata SVG compatta con separatore '.' */
function tp_svg_num(float $x): string
{
    return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.');
}

/** Importo compatto (simbolo + intero con separatore migliaia) per assi e etichette. */
function tp_money_compact(float $v, string $currency = 'EUR'): string
{
    $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF ', 'JPY' => '¥'];
    $sym = $symbols[strtoupper($currency)] ?? (strtoupper($currency) . ' ');

    return $sym . ' ' . number_format(round($v), 0, ',', '.');
}

/**
 * Grafico a torta (SVG inline + legenda)
 * interattivo via CSS
 * ogni spicchio è un arco `stroke-dasharray`; al passaggio
 * del mouse lo spicchio si ispessisce, gli altri sfumano e il centro mostra
 * etichetta, valore e percentuale dello spicchio puntato.
 */
function donut_chart(array $items, array $opts = []): string
{
    $centerLabel = (string) ($opts['center_label'] ?? '');
    $emptyText   = (string) ($opts['empty_text'] ?? 'Nessun dato disponibile.');
    $unit        = (string) ($opts['unit'] ?? '');

    // Palette di fallback 
    $palette = [
        'var(--tp-primary)',
        'var(--tp-success)',
        'var(--tp-accent)',
        'hsl(210deg 70% 60%)',
        'hsl(265deg 55% 66%)',
        'var(--tp-danger)',
    ];

    $clean = [];
    $total = 0.0;
    foreach ($items as $idx => $it) {
        $v = (float) ($it['value'] ?? 0);
        if ($v < 0) {
            $v = 0.0;
        }
        $clean[] = [
            'label' => (string) ($it['label'] ?? ''),
            'value' => $v,
            'color' => ($it['color'] ?? '') !== '' ? (string) $it['color'] : $palette[$idx % count($palette)],
        ];
        $total += $v;
    }

    if ($clean === [] || $total <= 0) {
        return '<div class="tp-combo-empty">' . icon('trending', '', 40)
            . '<p>' . e($emptyText) . '</p></div>';
    }

    // Geometria: anello centrato in (100,100), raggio mediano 70, spessore 24.
    $cxc = 100.0; $cyc = 100.0; $R = 70.0;
    $C = 2 * M_PI * $R;

    $segs = ''; $legend = '';
    $acc = 0.0;
    foreach ($clean as $s) {
        $frac = $s['value'] / $total;
        $pct  = $frac * 100;
        // Spicchi a zero non disegnano arco; sugli altri lascio un piccolo stacco.
        $dash = $frac <= 0 ? 0.0 : max($frac * $C - 2.0, 1.0);
        $off  = -$acc * $C;
        $acc += $frac;

        $valFmt = number_format($s['value'], 0, ',', '.');
        $pctFmt = number_format($pct, 1, ',', '.');
        $titleTxt = e($s['label'] . ' — ' . $valFmt
            . ($unit !== '' ? ' ' . $unit : '') . ' · ' . $pctFmt . '%');

        $segs .= '<g class="tp-donut-seg" style="--seg:' . e($s['color']) . '">'
            . '<title>' . $titleTxt . '</title>'
            . '<circle class="tp-donut-arc" cx="' . tp_svg_num($cxc) . '" cy="' . tp_svg_num($cyc) . '" r="' . tp_svg_num($R) . '"'
            . ' stroke-dasharray="' . tp_svg_num($dash) . ' ' . tp_svg_num($C) . '"'
            . ' stroke-dashoffset="' . tp_svg_num($off) . '"'
            . ' transform="rotate(-90 ' . tp_svg_num($cxc) . ' ' . tp_svg_num($cyc) . ')"/>'
            . '<g class="tp-donut-cap">'
            . '<circle class="tp-donut-cap-bg" cx="' . tp_svg_num($cxc) . '" cy="' . tp_svg_num($cyc) . '" r="56"/>'
            . '<text class="tp-donut-cap-pct" x="' . tp_svg_num($cxc) . '" y="' . tp_svg_num($cyc - 8) . '">' . e($pctFmt) . '%</text>'
            . '<text class="tp-donut-cap-label" x="' . tp_svg_num($cxc) . '" y="' . tp_svg_num($cyc + 12) . '">' . e($s['label']) . '</text>'
            . '<text class="tp-donut-cap-sub" x="' . tp_svg_num($cxc) . '" y="' . tp_svg_num($cyc + 28) . '">' . e($valFmt . ($unit !== '' ? ' ' . $unit : '')) . '</text>'
            . '</g>'
            . '</g>';

        $legend .= '<li class="tp-donut-li">'
            . '<span class="tp-donut-swatch" style="background:' . e($s['color']) . '"></span>'
            . '<span class="tp-donut-li-label">' . e($s['label']) . '</span>'
            . '<span class="tp-donut-li-val">' . e($valFmt) . '</span>'
            . '<span class="tp-donut-li-pct">' . e($pctFmt) . '%</span>'
            . '</li>';
    }

    $totFmt = number_format($total, 0, ',', '.');
    $center = '<g class="tp-donut-total">'
        . '<text class="tp-donut-total-value" x="' . tp_svg_num($cxc) . '" y="' . tp_svg_num($cyc - 2) . '">' . e($totFmt) . '</text>'
        . ($centerLabel !== ''
            ? '<text class="tp-donut-total-label" x="' . tp_svg_num($cxc) . '" y="' . tp_svg_num($cyc + 16) . '">' . e($centerLabel) . '</text>'
            : '')
        . '</g>';

    $svg = '<svg class="tp-donut-svg" viewBox="0 0 200 200" role="img" aria-label="' . e($centerLabel !== '' ? $centerLabel : 'Composizione') . '">'
        . '<circle class="tp-donut-track" cx="' . tp_svg_num($cxc) . '" cy="' . tp_svg_num($cyc) . '" r="' . tp_svg_num($R) . '"/>'
        . $center
        . '<g class="tp-donut-segs">' . $segs . '</g>'
        . '</svg>';

    return '<figure class="tp-donut">'
        . '<div class="tp-donut-figure">' . $svg . '</div>'
        . '<ul class="tp-donut-legend">' . $legend . '</ul>'
        . '</figure>';
}

/**
 * bottone "torna indietro" 
 */
function back_button(string $href, string $label = 'Indietro'): string
{
    if ($href === '#back') {
        return sprintf(
            '<a href="javascript:history.back()" class="button is-ghost is-small tp-back">%s<span>%s</span></a>',
            icon('arrow-left'),
            e($label)
        );
    }
    return sprintf(
        '<a href="%s" class="button is-ghost is-small tp-back">%s<span>%s</span></a>',
        e(url($href)),
        icon('arrow-left'),
        e($label)
    );
}

/**
 * Path query-string per l'elenco prenotazioni pilota.
 */
function prenotazione_lista_url(string $stato, string $q = ''): string
{
    $qs = ['stato' => $stato];
    if ($q !== '') {
        $qs['q'] = $q;
    }

    return '/prenotazioni?' . http_build_query($qs);
}

/** Path elenco veicoli flotta per circuito con filtro categoria opzionale */
function flotta_qs(int $circuitoId, string $cat = ''): string
{
    $path = '/flotta/circuito/' . max(0, $circuitoId);

    return $cat !== '' ? $path . '?cat=' . rawurlencode($cat) : $path;
}