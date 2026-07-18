<?php

declare(strict_types=1);

/**
 * Gestione dei tassi di cambio e conversione in EUR.   
 */
final class FCambioValuta
{
    /** Valute offerte nel selettore (ISO 4217). */
    public const SUPPORTATE = ['EUR', 'USD', 'GBP', 'CHF', 'JPY'];

    /** Validità della cache dei tassi: 6 ore. */
    private const TTL = 21600;

    /** Tassi di fallback (1 EUR = …) usati se il servizio non risponde. */
    private const FALLBACK = [
        'EUR' => 1.0,
        'USD' => 1.08,
        'GBP' => 0.85,
        'CHF' => 0.96,
        'JPY' => 170.0,
    ];

    private const ENDPOINT = 'https://api.frankfurter.app/latest';

    /**
     * Restituisce i tassi di cambio correnti (EUR come base) con cache.
     * Se il servizio non risponde, restituisce l'ultima cache disponibile (anche scaduta) o, in mancanza, i tassi di fallback.
     */
    public static function tassi(): array
    {
        $cache = self::leggiCache();
        if ($cache !== null && (time() - (int) $cache['fetched_at']) < self::TTL) {
            return self::formatta($cache, false);
        }

        $fresco = self::scarica();
        if ($fresco !== null) {
            self::scriviCache($fresco);
            return self::formatta($fresco, false);
        }

        // Servizio non raggiungibile: usa l'ultima cache (anche scaduta)
        if ($cache !== null) {
            return self::formatta($cache, true);
        }

        // … oppure la tabella statica di fallback.
        return [
            'base'  => 'EUR',
            'date'  => date('Y-m-d'),
            'rates' => self::FALLBACK,
            'stale' => true,
        ];
    }

    /**
     * Converte un importo da una valuta supportata in EUR.
     */
    public static function inEuro(float $importo, string $valuta): float
    {
        $valuta = strtoupper(trim($valuta));
        if ($valuta === '' || $valuta === 'EUR') {
            return round($importo, 2);
        }

        $tassi = self::tassi();
        $rate  = (float) ($tassi['rates'][$valuta] ?? 0.0);
        if ($rate <= 0) {
            throw new InvalidArgumentException('Valuta non supportata: ' . $valuta);
        }

        return round($importo / $rate, 2);
    }

    /**
     * Restituisce l'importo in EUR e il tasso di cambio usato.
     */
    private static function formatta(array $raw, bool $stale): array
    {
        $rates = ['EUR' => 1.0];
        foreach (self::SUPPORTATE as $code) {
            if (isset($raw['rates'][$code])) {
                $rates[$code] = (float) $raw['rates'][$code];
            }
        }

        return [
            'base'  => 'EUR',
            'date'  => (string) ($raw['date'] ?? date('Y-m-d')),
            'rates' => $rates,
            'stale' => $stale,
        ];
    }

    /**
     * Scarica i tassi di cambio correnti dal servizio esterno.
     */
    private static function scarica(): ?array
    {
        $symbols = implode(',', array_filter(self::SUPPORTATE, static fn($c) => $c !== 'EUR'));
        $url = self::ENDPOINT . '?base=EUR&symbols=' . rawurlencode($symbols);

        $body = self::http($url);
        if ($body === null) {
            return null;
        }

        try {
            $data = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($data) || !isset($data['rates']) || !is_array($data['rates'])) {
            return null;
        }

        $rates = ['EUR' => 1.0];
        foreach ($data['rates'] as $code => $value) {
            if (is_string($code) && is_numeric($value)) {
                $rates[strtoupper($code)] = (float) $value;
            }
        }

        return [
            'fetched_at' => time(),
            'date'       => (string) ($data['date'] ?? date('Y-m-d')),
            'rates'      => $rates,
        ];
    }

    /** Esegue la GET HTTP (cURL se disponibile, altrimenti stream) con timeout breve. */
    private static function http(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_USERAGENT      => 'TrackPortal/1.0 (+currency)',
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
        }

        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'timeout' => 5,
            'header'  => "Accept: application/json\r\nUser-Agent: TrackPortal/1.0\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);

        return $body !== false ? $body : null;
    }

    private static function cachePath(): string
    {
        return TRACKPORTAL_BASE_DIR . '/var/cache/valute.json';
    }

    /**
     * Restituisce true se i tassi di cambio correnti sono stati scaricati con successo dal servizio esterno.
     */
    private static function leggiCache(): ?array
    {
        $path = self::cachePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['fetched_at'], $data['rates']) || !is_array($data['rates'])) {
            return null;
        }

        return $data;
    }

    /**
     * Scrive la cache dei tassi di cambio correnti su file.
     */
    private static function scriviCache(array $data): void
    {
        $dir = dirname(self::cachePath());
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }
        @file_put_contents(
            self::cachePath(),
            json_encode($data, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}