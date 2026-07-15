<?php

/**
 * usecase: visualizzazione e ricerca (UC 5)
 * gestisce home page, ricerca e visualizzazione dei circuiti
 */

class CVisualizzazioneRicerca
{
    /** Azione invocata dall'URL*/
    public const DEFAULT_ACTION = 'elencaCircuiti';

    /** Tabella rotte «pulite» (URL → metodo); risolta da CFrontController. */
    public const ROUTES = [
        'GET cerca' => 'cercaCircuiti',
        'GET {id}' => 'visualizzaCircuito',
    ];

    /**
     * home di benvenuto, barra di ricerca ed elenco circuiti
     * chi ha già effettuato l'accesso ha redirect alla propria dashboard.
     */
    public function index(): void
    {
        if (CAuth::isLogged()) {
            redirect('/dashboard');
        }
        if (trim((string) ($_GET['q'] ?? '')) !== '') {
            $this->cercaCircuiti();
            return;
        }

        VHome::index(FPersistentManager::circuitoLoadWithVeicoliCount());
    }

}