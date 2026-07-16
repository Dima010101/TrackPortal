<?php

/**
 * controller per le pagine di errore
 */
class CError
{
    public const ROUTES = [
        'GET 404' => 'mostraNonTrovato',
        'GET 403' => 'mostraAccessoNegato',
    ];

    public function mostraNonTrovato(): void
    {
        VError::nonTrovato();
    }

    public function mostraAccessoNegato(): void
    {
        VError::accessoNegato();
    }
}
