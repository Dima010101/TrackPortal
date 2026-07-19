<?php

// view gestione profilo account
class VAccount extends VBase
{
    public static function gestioneAccount(
        array $profilo,
        ?array $extra,
        string $dashBack,
        array $form = [],
        array $errors = []
    ): void {
        self::render('profilo/gestione_account.tpl', 'Il mio account', null, [
            'profilo'   => $profilo,
            'extra'     => $extra,
            'dash_back' => $dashBack,
            'form'      => $form,
            'errors'    => $errors,
        ]);
    }

    public static function riepilogoModifiche(
        array $profiloAttuale,
        ?array $extraAttuale,
        array $pending,
        string $ruolo,
        string $dashBack
    ): void {
        self::render('profilo/riepilogo_modifiche.tpl', 'Riepilogo modifiche', null, [
            'profilo_attuale' => $profiloAttuale,
            'extra_attuale'   => $extraAttuale,
            'pending'         => $pending,
            'ruolo'           => $ruolo,
            'dash_back'       => $dashBack,
        ]);
    }

    public static function confermaModifica(
        array $profilo,
        string $dashBack,
        array $errors = []
    ): void {
        self::render('profilo/conferma_modifica.tpl', 'Modifica account', null, [
            'profilo'     => $profilo,
            'dash_back'   => $dashBack,
            'errors'      => $errors,
            'successo'    => $errors === [],
        ]);
    }

    // @deprecated usare gestioneAccount()
    public static function index(?array $extra, string $dashBack): void
    {
        $user = Session::get('user') ?? [];
        self::gestioneAccount(
            [
                'nome'    => (string) ($user['nome'] ?? ''),
                'cognome' => (string) ($user['cognome'] ?? ''),
                'email'   => (string) ($user['email'] ?? ''),
                'ruolo'   => (string) ($user['ruolo'] ?? ''),
            ],
            $extra,
            $dashBack
        );
    }
}
