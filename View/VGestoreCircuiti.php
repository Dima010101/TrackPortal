<?php


/**
 * VGestoreCircuiti — view dell'area gestore circuiti.
 */
class VGestoreCircuiti extends VBase
{
    public static function mieiCircuiti(
        array $rows,
        bool $canCreate = true,
        string $affiliazione = FPersistentManager::AFFILIAZIONE_STATO_APPROVATA
    ): void {
        self::render('gestore_circuiti/circuiti.tpl', 'I miei circuiti', null, [
            'rows'                 => $rows,
            'empty_list'           => $rows === [],
            'can_create'           => $canCreate,
            'affiliazione'         => $affiliazione,
            'affiliazione_attesa'  => $affiliazione === FPersistentManager::AFFILIAZIONE_STATO_IN_ATTESA,
        ]);
    }

    public static function formAggiungiCircuito(array $form = [], array $errors = []): void
    {
        self::render('gestore_circuiti/aggiungi_circuito.tpl', 'Aggiungi circuito', null, [
            'form'   => $form,
            'errors' => $errors,
        ]);
    }

    public static function formModificaCircuito(
        int $circuitoId,
        array $form = [],
        array $foto = [],
        array $errors = []
    ): void {
        self::render('gestore_circuiti/modifica_circuito.tpl', 'Modifica circuito', null, [
            'circuito_id' => $circuitoId,
            'form'        => $form,
            'foto'        => $foto,
            'errors'      => $errors,
        ]);
    }

    public static function confermaAggiuntaCircuito(array $circuito): void
    {
        self::render('gestore_circuiti/conferma_aggiunta_circuito.tpl', 'Circuito aggiunto', null, [
            'circuito' => $circuito,
        ]);
    }

    public static function calendario(
        array $circuiti,
        int $circuitoId,
        string $lunedi,
        array $griglia,
        array $ore,
        int $settimana,
        array $errors = [],
        bool $readonly = false,
        bool $canCreate = true,
        string $affiliazione = FPersistentManager::AFFILIAZIONE_STATO_APPROVATA
    ): void {
        $lun = new DateTimeImmutable($lunedi);
        $etichetteGiorni = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $lun->modify('+' . $i . ' days');
            $etichetteGiorni[] = [
                'data'   => $d->format('Y-m-d'),
                'label'  => $d->format('D d/m'),
            ];
        }

        self::render('gestore_circuiti/calendario.tpl', 'Calendario circuiti', null, [
            'circuiti'         => $circuiti,
            'circuito_id'      => $circuitoId,
            'lunedi'           => $lunedi,
            'etichette_giorni' => $etichetteGiorni,
            'griglia'          => $griglia,
            'ore'              => $ore,
            'settimana'        => $settimana,
            'errors'           => $errors,
            'cal_readonly'     => $readonly,
            'cal_nav_base'        => '/calendario',
            'cal_nav_query'       => 'circuito_id=' . $circuitoId . '&settimana=',
            'can_create'          => $canCreate,
            'affiliazione'        => $affiliazione,
            'affiliazione_attesa' => $affiliazione === FPersistentManager::AFFILIAZIONE_STATO_IN_ATTESA,
        ]);
    }

    public static function schedaSessione(
        ?array $circuito,
        string $data,
        string $ora,
        ?array $sessione,
        array $errors = [],
        int $settimana = 0,
        array $form = [],
        array $prenotazioni = [],
        array $idsSanzionabili = []
    ): void {
        $durata = 1;
        $posti  = 1;
        $postiPerBox = 1;
        $note   = '';
        $stato  = 'privata';
        $sessioneId = 0;
        $tariffa       = '';
        $tariffaValuta = 'EUR';

        if ($sessione !== null) {
            $sessioneId = (int) ($sessione['id'] ?? 0);
            $posti      = (int) ($sessione['posti_max'] ?? 1);
            $postiPerBox = (int) ($sessione['posti_per_box'] ?? 1);
            $note       = (string) ($sessione['note'] ?? '');
            $stato      = (string) ($sessione['stato'] ?? 'privata');
            $tariffa    = (string) ($sessione['tariffa_accesso'] ?? '');
            try {
                $ini = new DateTimeImmutable((string) $sessione['inizio']);
                $fin = new DateTimeImmutable((string) $sessione['fine']);
                $durata = max(1, (int) round(($fin->getTimestamp() - $ini->getTimestamp()) / 3600));
            } catch (Exception) {
                $durata = 1;
            }
        }

        if ($form !== []) {
            $durata     = (int) ($form['durata'] ?? $durata);
            $posti      = (int) ($form['posti_max'] ?? $posti);
            $postiPerBox = (int) ($form['posti_per_box'] ?? $postiPerBox);
            $note       = (string) ($form['note'] ?? $note);
            $stato      = (string) ($form['stato'] ?? $stato);
            $sessioneId = (int) ($form['sessione_id'] ?? $sessioneId);
            $tariffa       = (string) ($form['tariffa_accesso'] ?? $tariffa);
            $tariffaValuta = (string) ($form['tariffa_valuta'] ?? $tariffaValuta);
        }

        $confermate = 0;
        $incasso    = 0.0;
        $valuta     = 'EUR';
        foreach ($prenotazioni as &$p) {
            if (($p['stato'] ?? '') === 'confermata') {
                $confermate++;
                // Il gestore vede SOLO la propria quota (accesso pista), non il
                // totale pagato dal pilota (noleggio/assicurazione sono di terzi).
                $incasso += (float) ($p['imponibile_accesso'] ?? 0);
            }
            $valuta = (string) ($p['prezzo_valuta'] ?? $valuta);
            $p['gia_sanzionato'] = !in_array((int) ($p['pilota_id'] ?? 0), $idsSanzionabili, true);
        }
        unset($p);
        $prenotabile = ESessione::categoriaPilotaAmmessa($stato) !== null;

        self::render('gestore_circuiti/scheda_sessione.tpl', 'Scheda sessione', null, [
            'circuito'    => $circuito,
            'data'        => $data,
            'ora'         => $ora,
            'sessione'    => $sessione,
            'sessione_id' => $sessioneId,
            'durata'      => $durata,
            'tariffa_accesso' => $tariffa,
            'tariffa_valuta'  => $tariffaValuta,
            'valute'          => FPersistentManager::CAMBIO_VALUTA_SUPPORTATE,
            'posti_max'   => $posti,
            'posti_per_box' => $postiPerBox,
            'note'        => $note,
            'stato'       => $stato,
            'settimana'   => $settimana,
            'errors'      => $errors,
            'prenotazioni'   => $prenotazioni,
            'empty_list'     => $prenotazioni === [],
            'confermate'     => $confermate,
            'posti_liberi'   => max(0, $posti - $confermate),
            'prenotabile'    => $prenotabile,
            'incasso'        => $incasso,
            'incasso_valuta' => $valuta,
            'bloccato'    => $errors !== [] && $sessione === null
                && in_array('Lo slot selezionato è già occupato da una prenotazione.', $errors, true),
        ]);
    }

    public static function formAnnullaSessione(
        array $sessione,
        array $piloti,
        int $settimana = 0,
        array $errors = [],
        string $causa = ''
    ): void {
        $rimborsoTotale = 0.0;
        $valuta         = 'EUR';
        foreach ($piloti as $p) {
            $rimborsoTotale += (float) ($p['prezzo_importo'] ?? 0);
            $valuta = (string) ($p['prezzo_valuta'] ?? $valuta);
        }

        self::render('gestore_circuiti/annulla_sessione.tpl', 'Annulla sessione', null, [
            'sessione'         => $sessione,
            'piloti'           => $piloti,
            'rimborso_totale'  => $rimborsoTotale,
            'rimborso_valuta'  => $valuta,
            'settimana'        => $settimana,
            'errors'           => $errors,
            'causa'            => $causa,
        ]);
    }

    public static function storico(array $rows, string $q, array $idsSanzionabili = []): void
    {
        foreach ($rows as &$r) {
            $r['gia_sanzionato'] = !in_array((int) ($r['pilota_id'] ?? 0), $idsSanzionabili, true);
        }
        unset($r);

        self::render('gestore_circuiti/storico.tpl', 'Storico prenotazioni', null, [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

}
