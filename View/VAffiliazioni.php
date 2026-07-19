<?php

// view gestione richieste di affiliazione
class VAffiliazioni extends VBase
{
    public static function richiestePending(array $richieste, array $storico = []): void
    {
        self::render('affiliazioni/richieste_pending.tpl', 'Convalida', null, [
            'richieste'     => $richieste,
            'empty_list'    => $richieste === [],
            'storico'       => $storico,
            'empty_storico' => $storico === [],
        ]);
    }

    public static function dettaglioRichiesta(array $richiesta, array $errors = [], string $motivo = ''): void
    {
        self::render('affiliazioni/dettaglio_richiesta.tpl', 'Dettaglio richiesta', null, [
            'richiesta'        => $richiesta,
            'errors'           => $errors,
            'motivo'           => $motivo,
            'in_attesa'        => ($richiesta['affiliazione'] ?? '') === FPersistentManager::AFFILIAZIONE_STATO_IN_ATTESA,
            'is_pilota'        => ($richiesta['tipo'] ?? '') === EPilota::$ruolo,
            'documenti'        => self::documentiRichiesta($richiesta),
            'etichetta_licenza' => ((string) ($richiesta['categoria'] ?? '')) === 'professionista'
                ? 'Licenza ACI'
                : 'Patente di guida',
        ]);
    }

    public static function confermaApprovazione(array $richiesta): void
    {
        self::render('affiliazioni/conferma_approvazione.tpl', 'Richiesta approvata', null, [
            'richiesta' => $richiesta,
        ]);
    }

    public static function confermaRifiuto(array $richiesta, string $motivo = ''): void
    {
        self::render('affiliazioni/conferma_rifiuto.tpl', 'Richiesta respinta', null, [
            'richiesta' => $richiesta,
            'motivo'    => $motivo,
        ]);
    }

    public static function esitoOperazione(bool $successo, array $errors = []): void
    {
        self::render('affiliazioni/esito_operazione.tpl', 'Esito operazione', null, [
            'successo' => $successo,
            'errors'   => $errors,
        ]);
    }

    // dati documentali derivati dalla registrazione, non esiste tabella dedicata
    private static function documentiRichiesta(array $richiesta): array
    {
        return [
            ['label' => 'Ragione sociale', 'valore' => (string) ($richiesta['nome_societa'] ?? '')],
            ['label' => 'Partita IVA', 'valore' => (string) ($richiesta['partita_iva'] ?? '')],
            ['label' => 'Email referente', 'valore' => (string) ($richiesta['email'] ?? '')],
            ['label' => 'Data richiesta', 'valore' => (string) ($richiesta['data_creazione'] ?? '—')],
        ];
    }
}
