/*metodi di assistenza a tutte le classi */
<?php

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