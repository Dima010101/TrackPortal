<?php

// pagine di errore 403, 404, 500
class VError extends VBase
{
    public static function accessoNegato(array $allowed = []): void
    {
        http_response_code(403);
        $labels = array_map('ruolo_label', $allowed);
        self::render(
            'errori/accesso_negato.tpl',
            'Accesso negato',
            'Non hai i permessi per accedere a questa risorsa.',
            [
                'allowed'        => $allowed,
                'allowed_labels' => $labels ? implode(', ', $labels) : '',
            ]
        );
    }

    public static function nonTrovato(): void
    {
        http_response_code(404);
        self::render(
            'errori/non_trovato.tpl',
            'Pagina non trovata',
            'La risorsa richiesta non esiste o e stata rimossa.'
        );
    }

    // mostrata quando una richiesta che richiede i cookie arriva senza di essi
    public static function cookieDisabilitati(): void
    {
        http_response_code(400);
        self::render(
            'errori/cookie_disabilitati.tpl',
            'Cookie non abilitati',
            'Per usare questa funzione è necessario abilitare i cookie del browser.'
        );
    }

    // eccezione non gestita risalita al front controller, dettagli solo con APP_DEBUG
    public static function erroreInterno(?Throwable $e = null): void
    {
        http_response_code(500);
        self::render(
            'errori/errore_interno.tpl',
            'Errore interno',
            'Si è verificato un errore imprevisto durante l\'elaborazione della richiesta.',
            ['dettagli' => self::dettagliDebug($e)]
        );
    }

    private static function dettagliDebug(?Throwable $e): string
    {
        if ($e === null || !APP_DEBUG) {
            return '';
        }

        return sprintf(
            "%s: %s\n%s:%d\n\n%s",
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}
