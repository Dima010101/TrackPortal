<?php


/**
 * VCircuito - view dell'elenco e del dettaglio circuiti.
 */
class VCircuito extends VBase
{
    /**
     * Elenco dei circuiti (card → scheda di dettaglio), riusa il partial già
     * impiegato in home; $q precompila la barra di ricerca (risultato ricerca).
     */
    public static function mostraElenco(array $circuiti, string $q = ''): void
    {
        self::render('circuiti/elenco.tpl', 'Tutti i circuiti', null, [
            'circuiti' => $circuiti,
            'q'        => $q,
        ]);
    }

    public static function mostraCircuitoNonTrovato(int $idCircuito): void
    {
        http_response_code(404);
        self::render('circuiti/non_trovato.tpl', 'Circuito non trovato', null, [
            'id_circuito' => $idCircuito,
        ]);
    }

    public static function mostraDettaglio(
        array $circuito,
        array $veicoliNoleggio,
        array $topVeicoli,
        ?array $calendario = null
    ): void {
        self::render('circuiti/dettaglio.tpl', (string) $circuito['nome_circuito'], null, [
            'circuito'     => $circuito,
            'veicoli_disp' => $veicoliNoleggio,
            'top_veicoli'  => $topVeicoli,
            'calendario'   => $calendario,
        ]);
    }
}
