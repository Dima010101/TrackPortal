<?php

/**
 * front controller dell'applicazione: per ogni richiesta ricostruisce la
 * classe di controllo dalla risorsa e risolve il metodo
 */
class CFrontController
{
    private const RISORSE = [
        //Visualizzazione e ricerca
        'home'             => ['CVisualizzazioneRicerca', 'index'],
        'circuiti'         => ['CVisualizzazioneRicerca', null],
        //Prenotazione sessione
        'prenotazione'     => ['CPrenotazioneSessione', null],
        //Gestione prenotazioni e storico
        'prenotazioni'     => ['CGestionePrenotazioniStorico', null],
        //Aggiornare la schedule del circuito
        'calendario'       => ['CAggiornareScheduleCircuito', null],
        //Gestione e aggiunta circuiti
        'circuitiGestore'  => ['CGestioneAggiuntaCircuiti', null],
        //Gestione flotta
        'flotta'           => ['CGestioneFlotta', null],
        //Gestione assicurazione, commissione e IVA
        'commissioni'      => ['CGestioneAssicurazioneCommissioneIva', null],
        //Gestione documenti di fatturazione
        'fatture'          => ['CGestioneDocumentiFatturazione', null],
        'templateFattura'  => ['CGestioneDocumentiFatturazione', 'gestisciTemplateFatture'],
        //Gestione e aggiunta promozioni
        'promozioni'       => ['CGestioneAggiuntaPromozioni', null],
        //Gestione account
        'account'          => ['CGestioneAccount', null],
        'documentoPilota'  => ['CGestioneAccount', null],
        //Approvazione affiliazione
        'affiliazioni'     => ['CApprovazioneAffiliazione', null],
        //Sospensione e ban
        'sanzioniGestore'  => ['CSospensioneBan', null],
        'sanzioniNoleggio' => ['CSospensioneBan', null],
    ];

    private const RISORSE_API = [
        'valute' => ['CPrenotazioneSessione', 'tassiCambio'],
    ];

    /**
     * avvia l'applicazione per la richiesta HTTP corrente
     */
    public function run(): void
    {
        $segments = $this->segmenti($_SERVER['REQUEST_URI'] ?? '/');

        if (($segments[0] ?? null) === 'api') {
            $this->dispatchApi(array_slice($segments, 1));
            return;
        }

        if ($this->metodoHttp() === 'POST' && !cookie_sessione_presente()) {
            VError::cookieDisabilitati();
            return;
        }

        $this->dispatch($segments);
    }

    /**
     * ricostruisce controller + azione dai segmenti
     */
    private function dispatch(array $segments): void
    {
        $resource = $segments[0] ?? 'home';

        [$controller, $default] = self::RISORSE[$resource] ?? ['C' . ucfirst($resource), null];

        if (!class_exists($controller)) {
            $this->nonTrovato(false);
            return;
        }

        [$action, $params] = $this->risolvi($controller, array_slice($segments, 1), $this->metodoHttp(), $default);

        if ($action === null || !$this->azioneInvocabile($controller, $action, count($params))) {
            $this->nonTrovato(false);
            return;
        }

        (new $controller())->$action(...$params);
    }

    /**
     * ramo api
     */
    private function dispatchApi(array $segments): void
    {
        $spec = count($segments) === 1 ? (self::RISORSE_API[$segments[0]] ?? null) : null;

        if ($spec === null) {
            $this->nonTrovato(true);
            return;
        }

        [$controller, $action] = $spec;
        if (!$this->azioneInvocabile($controller, $action, 0)) {
            $this->nonTrovato(true);
            return;
        }

        (new $controller())->$action();
    }

    /**
     * risolve i segmenti che seguono la risorsa in [metodo, parametri]
     */
    private function risolvi(string $controller, array $rest, string $method, ?string $default = null): array
    {
        if ($rest === []) {
            return [$default ?? $this->azionePredefinita($controller), []];
        }

        $routes = defined($controller . '::ROUTES') ? (array) constant($controller . '::ROUTES') : [];
        foreach ($routes as $spec => $target) {
            [$verb, $pattern] = array_pad(explode(' ', (string) $spec, 2), 2, '');
            if ($verb !== $method) {
                continue;
            }
            $params = $this->matchPattern($pattern, $rest);
            if ($params !== null) {
                return [(string) $target, $params];
            }
        }

        return [null, []];
    }

    /**
     * confronta un pattern di rotta con i segmenti dell'URL
     * ritorna i parametri catturati o null se non combacia
     */
    private function matchPattern(string $pattern, array $rest): ?array
    {
        $patSegs = $pattern === '' ? [] : explode('/', $pattern);
        if (count($patSegs) !== count($rest)) {
            return null;
        }

        $params = [];
        foreach ($patSegs as $i => $ps) {
            if ($ps === '{id}') {
                if (!ctype_digit($rest[$i])) {
                    return null;
                }
                $params[] = $rest[$i];
            } elseif ($ps === '{p}') {
                $params[] = $rest[$i];
            } elseif ($ps !== $rest[$i]) {
                return null;
            }
        }

        return $params;
    }

    /**
     * azione predefinita di un controller quando l'URL non specifica un'azione
     */
    private function azionePredefinita(string $controller): string
    {
        if (class_exists($controller) && defined($controller . '::DEFAULT_ACTION')) {
            return (string) constant($controller . '::DEFAULT_ACTION');
        }

        return 'index';
    }

    /**
     * vero se $controller::$action esiste, è un metodo d'azione pubblico
     */
    private function azioneInvocabile(string $controller, string $action, int $argc): bool
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            return false;
        }

        $metodo = new ReflectionMethod($controller, $action);
        if (!$metodo->isPublic() || $metodo->isStatic() || $metodo->isAbstract()) {
            return false;
        }

        $minimi  = $metodo->getNumberOfRequiredParameters();
        $massimi = $metodo->isVariadic() ? PHP_INT_MAX : $metodo->getNumberOfParameters();

        return $argc >= $minimi && $argc <= $massimi;
    }

    /**
     * pagina 404 o payload JSON 404
     */
    private function nonTrovato(bool $json): void
    {
        if ($json) {
            json_print(['error' => 'Endpoint not found'], 404);
            return;
        }

        VError::nonTrovato();
    }

    /**
     * metodo HTTP della richiesta
     */
    private function metodoHttp(): string
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        return $metodo === 'HEAD' ? 'GET' : $metodo;
    }

    /**
     * normalizza l'URI in segmenti di percorso
     */
    private function segmenti(string $requestUri): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        $base = rtrim(APP_BASE_URL, '/');
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        $path = rawurldecode('/' . ltrim($path, '/'));

        return array_values(array_filter(
            explode('/', $path),
            static fn(string $s): bool => $s !== ''
        ));
    }
}
