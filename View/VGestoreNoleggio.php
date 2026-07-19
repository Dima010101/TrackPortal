<?php


/**
 * VGestoreNoleggio — view dell'area azienda di noleggio (gestione flotta).
 */
class VGestoreNoleggio extends VBase
{
    
    public static function storico(array $rows, string $q, array $idsSanzionabili = []): void
    {
        foreach ($rows as &$r) {
            $r['gia_sanzionato'] = !in_array((int) ($r['pilota_id'] ?? 0), $idsSanzionabili, true);
        }
        unset($r);

        self::render('gestore_noleggio/storico.tpl', 'Storico noleggi', null, [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    
    public static function elencoCircuiti(array $circuiti, array $errors = []): void
    {
        self::render('gestore_noleggio/flotta_circuiti.tpl', 'Gestione flotta', null, [
            'circuiti' => $circuiti,
            'errors'   => $errors,
        ]);
    }

    
    public static function elencoVeicoli(
        ?array $circuito,
        array $veicoli,
        string $catFilter = '',
        array $errors = []
    ): void {
        self::render('gestore_noleggio/flotta_veicoli.tpl', 'Veicoli in flotta', null, [
            'circuito'   => $circuito,
            'veicoli'    => $veicoli,
            'cat_filter' => $catFilter,
            'errors'     => $errors,
        ]);
    }

    
    public static function dettaglioVeicolo(?array $circuito, ?array $veicolo, array $errors = []): void
    {
        self::render('gestore_noleggio/flotta_dettaglio.tpl', 'Dettaglio veicolo', null, [
            'circuito' => $circuito,
            'veicolo'  => $veicolo,
            'errors'   => $errors,
        ]);
    }

    
    public static function formAggiungiVeicolo(
        array $circuiti,
        array $form = [],
        array $errors = []
    ): void {
        $dati = [
            'circuito_id'   => (string) ($form['circuito_id'] ?? ''),
            'targa'         => (string) ($form['targa'] ?? ''),
            'categoria'     => (string) ($form['categoria'] ?? 'auto'),
            'marca'         => (string) ($form['marca'] ?? ''),
            'modello'       => (string) ($form['modello'] ?? ''),
            'anno'          => (string) ($form['anno'] ?? ''),
            'potenza_cv'    => (string) ($form['potenza_cv'] ?? ''),
            'capienza'      => (string) ($form['capienza'] ?? '1'),
            'prezzo_giorno' => (string) ($form['prezzo_giorno'] ?? ''),
            'prezzo_valuta' => (string) ($form['prezzo_valuta'] ?? 'EUR'),
            'disponibile'   => (string) ($form['disponibile'] ?? '1'),
        ];

        self::render('gestore_noleggio/flotta_aggiungi.tpl', 'Aggiungi veicolo', null, [
            'circuiti' => $circuiti,
            'form'     => $dati,
            'valute'   => FPersistentManager::CAMBIO_VALUTA_SUPPORTATE,
            'errors'   => $errors,
        ]);
    }

    
    public static function formModificaVeicolo(
        ?array $circuito,
        ?array $veicolo,
        array $circuiti = [],
        array $form = [],
        array $errors = []
    ): void {
        $dati = [
            'veicolo_id'    => (int) ($veicolo['id'] ?? ($form['veicolo_id'] ?? 0)),
            'circuito_id'   => (string) ($form['circuito_id'] ?? $veicolo['circuito_id'] ?? ''),
            'targa'         => (string) ($form['targa'] ?? $veicolo['targa'] ?? ''),
            'categoria'     => (string) ($form['categoria'] ?? $veicolo['categoria'] ?? 'auto'),
            'marca'         => (string) ($form['marca'] ?? $veicolo['marca'] ?? ''),
            'modello'       => (string) ($form['modello'] ?? $veicolo['modello'] ?? ''),
            'anno'          => (string) ($form['anno'] ?? $veicolo['anno'] ?? ''),
            'potenza_cv'    => (string) ($form['potenza_cv'] ?? $veicolo['potenza_cv'] ?? ''),
            'capienza'      => (string) ($form['capienza'] ?? $veicolo['capienza'] ?? '1'),
            'prezzo_giorno' => (string) ($form['prezzo_giorno'] ?? $veicolo['prezzo'] ?? ''),
            'prezzo_valuta' => (string) ($form['prezzo_valuta'] ?? 'EUR'),
            'disponibile'   => (string) ($form['disponibile'] ?? (($veicolo['disponibile'] ?? true) ? '1' : '0')),
        ];

        self::render('gestore_noleggio/flotta_modifica.tpl', 'Modifica veicolo', null, [
            'circuito' => $circuito,
            'circuiti' => $circuiti,
            'veicolo'  => $veicolo,
            'form'     => $dati,
            'valute'   => FPersistentManager::CAMBIO_VALUTA_SUPPORTATE,
            'errors'   => $errors,
        ]);
    }

    
    public static function riepilogo(?array $circuito, array $veicolo): void
    {
        self::render('gestore_noleggio/flotta_riepilogo.tpl', 'Veicolo aggiornato', null, [
            'circuito' => $circuito,
            'veicolo'  => $veicolo,
        ]);
    }

    
    public static function confermaAggiuntaVeicolo(?array $circuito, array $veicolo): void
    {
        self::render('gestore_noleggio/flotta_conferma_aggiunta.tpl', 'Veicolo aggiunto', null, [
            'circuito' => $circuito,
            'veicolo'  => $veicolo,
        ]);
    }
}
