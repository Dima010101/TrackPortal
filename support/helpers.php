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