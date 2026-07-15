<?php

/**
 * usecase: visualizzazione e ricerca (UC 5)
 * gestisce home page, ricerca e visualizzazione dei circuiti
 */

class CVisualizzazioneRicerca
{
    /** Azione default invocata dall'URL*/
    public const DEFAULT_ACTION = 'elencaCircuiti';

    /** redirect url metodo */
    public const ROUTES = [
        'GET cerca' => 'cercaCircuiti',
        'GET {id}' => 'visualizzaCircuito',
    ];

    /**home di benvenuto, barra di ricerca ed elenco circuiti
     * chi ha già effettuato l'accesso ha redirect alla propria dashboard.
     */
    public function index(): void
    {
        if (CAuth::isLogged()) {
            redirect('/dashboard');
        }
        /**se digitato qualcosa nella barra di ricerca rimando a cercacircuiti */
        if (trim((string) ($_GET['q'] ?? '')) !== '') {
            $this->cercaCircuiti();
            return;
        }

        VHome::index(FPersistentManager::circuitoLoadWithVeicoliCount());
    }

    /** funzione per cercare i circuiti */
    public function cercaCircuiti(string $keyword = ''): void
    {
        $keyword = trim($keyword !== '' ? $keyword : (string) ($_GET['q'] ?? ''));

        VCircuito::mostraElenco(
            self::filtraPerParola(FPersistentManager::circuitoLoadWithVeicoliCount(), $keyword),
            $keyword
        );
    }

    /**elenco dei circuiti completo*/
    public function elencaCircuiti(): void
    {
        if (trim((string) ($_GET['q'] ?? '')) !== '') {
            $this->cercaCircuiti();
            return;
        }

        VCircuito::mostraElenco(FPersistentManager::circuitoLoadWithVeicoliCount());
    }

    /** mostra scheda del cirucito */
    public function visualizzaCircuito(int|string $id = 0): void
    {
        $idCircuito = (int) $id;
        $circuito   = $idCircuito > 0 ? FPersistentManager::circuitoLoadById($idCircuito) : null;
        if (!$circuito) {
            VCircuito::mostraCircuitoNonTrovato($idCircuito);
            return;
        }

        VCircuito::mostraDettaglio(
            $circuito,
            FPersistentManager::veicoloNoleggioLoadDisponibiliByCircuito($idCircuito),
            FPersistentManager::veicoloNoleggioLoadTopByCircuito($idCircuito, 3),
            self::datiCalendario($idCircuito)
        );
    }

    /** filtra l'elenco circuiti per keyword su nome, località e indirizzo */
    private static function filtraPerParola(array $circuiti, string $keyword): array
    {
        if ($keyword === '') {
            return $circuiti;
        }

        return array_values(array_filter(
            $circuiti,
            static function (array $c) use ($keyword): bool {
                $testo = ($c['nome_circuito'] ?? '') . ' ' . ($c['localita'] ?? '') . ' ' . ($c['indirizzo'] ?? '');

                return mb_stripos($testo, $keyword) !== false;
            }
        ));
    }

    /**Griglia calendario del circuito e verifica se un utente si può prenotare */
    private static function datiCalendario(int $idCircuito): array
    {
        $user     = CAuth::utenteCorrente();
        $pilotaId = $user !== null ? (int) $user['id'] : null;

        $calendario = FPersistentManager::sessioneCalendarioSettimanale(
            $idCircuito,
            (int) ($_GET['settimana'] ?? 0),
            $pilotaId
        );
        $calendario['cal_readonly']       = true;
        $calendario['cal_nav_base']       = '/circuiti/' . $idCircuito;
        $calendario['cal_nav_query']      = 'settimana=';
        $calendario['cal_prenotabile']    = $user !== null && $user['ruolo'] === EPilota::$ruolo;
        $calendario['cal_richiede_login'] = $user === null;

        return $calendario;
    }

}