<?php


/**
 * VDashboard - pagine iniziali per ruolo (home del pilota, dashboard di gestore/noleggio/admin).
 */
class VDashboard extends VBase
{
    private static function renderDashboard(string $template, string $title, array $vars = []): void
    {
        self::render($template, $title, null, array_merge($vars, [
            'page_main_class' => 'tp-page--dashboard',
        ]));
    }

    /**
     * Trasforma le righe mensili grezze ({ym, prenotazioni, ricavi}) nella serie
     * attesa dal grafico combinato: {label, bar, line}. La linea (ricavi) può
     * essere ricalcolata da un valore unitario (es. assicurazioni = conteggio × prezzo)
     */
    private static function serieAndamento(array $rows, float $valoreUnitario = 0.0): array
    {
        $serie = [];
        foreach ($rows as $r) {
            $bar = (int) ($r['prenotazioni'] ?? 0);
            $serie[] = [
                'label' => mese_breve_it((string) ($r['ym'] ?? '')),
                'bar'   => $bar,
                'line'  => $valoreUnitario > 0 ? $bar * $valoreUnitario : (float) ($r['ricavi'] ?? 0),
            ];
        }

        return $serie;
    }

    public static function home(array $prossime, array $profilo = [], array $circuiti = []): void
    {
        self::renderDashboard('home/home.tpl', 'Home', [
            'prossime' => $prossime,
            'profilo'  => $profilo,
            'circuiti' => $circuiti,
        ]);
    }

    public static function gestore(
        int $sessioniConcluse,
        int $prenAttive,
        int $promoAttive,
        float $guadagnoMese,
        string $affiliazione = FPersistentManager::AFFILIAZIONE_STATO_APPROVATA,
        array $andamento = [],
        array $circuiti = [],
        int $circuitoSel = 0,
        int $mesi = 6
    ): void {
        // Nome del circuito selezionato per l'etichetta del selettore.
        $nomeCircuitoSel = '';
        foreach ($circuiti as $c) {
            if ((int) ($c['id'] ?? 0) === $circuitoSel) {
                $nomeCircuitoSel = (string) ($c['nome_circuito'] ?? '');
                break;
            }
        }

        self::renderDashboard('home/gestore.tpl', 'Dashboard gestore', [
            'sessioni_concluse'   => $sessioniConcluse,
            'pren_attive'         => $prenAttive,
            'promo_attive'        => $promoAttive,
            'guadagno_mese'       => $guadagnoMese,
            'affiliazione'        => $affiliazione,
            'affiliazione_attesa' => $affiliazione === FPersistentManager::AFFILIAZIONE_STATO_IN_ATTESA,
            'affiliazione_rifiutata' => $affiliazione === FPersistentManager::AFFILIAZIONE_STATO_RIFIUTATA,
            'andamento'           => self::serieAndamento($andamento),
            'circuiti'            => $circuiti,
            'circuito_sel'        => $circuitoSel,
            'circuito_sel_nome'   => $nomeCircuitoSel,
            'mesi'                => $mesi,
        ]);
    }

    public static function noleggio(
        int $totVeicoli,
        int $totDispo,
        int $totPren,
        float $guadagno,
        array $topVeicoli = [],
        array $perCircuito = [],
        array $prossimi = [],
        array $andamento = [],
        int $mesi = 6
    ): void {
        self::renderDashboard('home/noleggio.tpl', 'Dashboard noleggio', [
            'tot_veicoli'  => $totVeicoli,
            'tot_dispo'    => $totDispo,
            'tot_pren'     => $totPren,
            'guadagno'     => $guadagno,
            'top_veicoli'  => $topVeicoli,
            'per_circuito' => $perCircuito,
            'prossimi'     => $prossimi,
            'andamento'    => self::serieAndamento($andamento),
            'mesi'         => $mesi,
        ]);
    }

    public static function admin(
        int $totUtenti,
        int $totCircuiti,
        int $totPren,
        int $totAssicur,
        int $pendingAff,
        float $prezzoAss,
        string $pctComm,
        float $ricavi = 0.0,
        float $commissioni = 0.0,
        array $utentiPerRuolo = [],
        array $ultimePren = [],
        int $pendingCirc = 0,
        int $pendingNol = 0,
        int $pendingPiloti = 0,
        array $andamentoAssicurazioni = [],
        int $mesi = 6
    ): void {
        $serieAssicur = self::serieAndamento($andamentoAssicurazioni, $prezzoAss);
        $ricaviAssicur = 0.0;
        foreach ($serieAssicur as $punto) {
            $ricaviAssicur += (float) $punto['line'];
        }

        // Composizione utenti per il donut: etichetta leggibile + colore per ruolo.
        $coloriRuolo = [
            'pilota'           => 'var(--tp-primary)',
            'gestore_circuiti' => 'var(--tp-success)',
            'gestore_noleggio' => 'var(--tp-accent)',
            'admin'            => 'hsl(210deg 70% 60%)',
        ];
        $composizioneUtenti = [];
        foreach ($utentiPerRuolo as $ruolo => $n) {
            $composizioneUtenti[] = [
                'label' => ruolo_label((string) $ruolo),
                'value' => (int) $n,
                'color' => $coloriRuolo[$ruolo] ?? '',
            ];
        }

        self::renderDashboard('home/amministratore.tpl', 'Dashboard amministratore', [
            'tot_utenti'        => $totUtenti,
            'tot_circuiti'      => $totCircuiti,
            'tot_pren'          => $totPren,
            'tot_assicur'       => $totAssicur,
            'pending_aff'       => $pendingAff,
            'pending_circ'      => $pendingCirc,
            'pending_nol'       => $pendingNol,
            'pending_piloti'    => $pendingPiloti,
            'prezzo_ass'        => $prezzoAss,
            'pct_comm'          => $pctComm,
            'ricavi'            => $ricavi,
            'commissioni'       => $commissioni,
            'utenti_per_ruolo'  => $utentiPerRuolo,
            'composizione_utenti' => $composizioneUtenti,
            'ultime_pren'       => $ultimePren,
            'andamento_assicur' => $serieAssicur,
            'mesi'              => $mesi,
            'ricavi_assicur'    => $ricaviAssicur,
            'pct_assicur'       => $totPren > 0 ? round($totAssicur / $totPren * 100, 1) : 0.0,
        ]);
    }
}
