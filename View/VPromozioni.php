<?php

/**
 * VPromozioni — view per la gestione promozioni (UC10).
 */
class VPromozioni extends VBase
{
    
    public static function selezioneEntita(
        array $entita,
        string $tipoEntita,
        string $ruolo,
        string $dashBack,
        bool $canCreateCircuiti = true,
        array $promoAttive = [],
        array $promoPassate = []
    ): void {
        self::render('promozioni/gestione_promozioni.tpl', 'Promozioni', null, [
            'entita'              => $entita,
            'tipo_entita'         => $tipoEntita,
            'ruolo'               => $ruolo,
            'dash_back'           => $dashBack,
            'empty_list'          => $entita === [],
            'can_create_circuiti' => $canCreateCircuiti,
            'promo_attive'        => $promoAttive,
            'promo_passate'       => $promoPassate,
        ]);
    }

    
    public static function elencoPromozioni(
        array $promo,
        array $entita,
        int $entitaId,
        string $tipoEntita,
        string $ruolo,
        string $dashBack
    ): void {
        self::render('promozioni/promozioni_servizio.tpl', 'Promozioni servizio', null, [
            'promo'         => $promo,
            'entita'        => $entita,
            'entita_id'     => $entitaId,
            'tipo_entita'   => $tipoEntita,
            'ruolo'         => $ruolo,
            'dash_back'     => $dashBack,
            'empty_list'    => $promo === [],
            'conferma_ok'   => false,
            'promo_creata'  => null,
        ]);
    }

    public static function formAggiungiPromozione(
        array $entita,
        int $entitaId,
        string $tipoEntita,
        string $ruolo,
        string $dashBack,
        array $form = [],
        array $errors = []
    ): void {
        self::render('promozioni/aggiungi_promozione.tpl', 'Nuova promozione', null, [
            'entita'      => $entita,
            'entita_id'   => $entitaId,
            'tipo_entita' => $tipoEntita,
            'ruolo'       => $ruolo,
            'dash_back'   => $dashBack,
            'form'        => $form,
            'errors'      => $errors,
        ]);
    }

    
    public static function formModificaPromozione(
        int $promoId,
        array $entita,
        int $entitaId,
        string $tipoEntita,
        string $ruolo,
        string $dashBack,
        array $form = [],
        array $errors = []
    ): void {
        self::render('promozioni/modifica_promozione.tpl', 'Modifica promozione', null, [
            'promo_id'    => $promoId,
            'entita'      => $entita,
            'entita_id'   => $entitaId,
            'tipo_entita' => $tipoEntita,
            'ruolo'       => $ruolo,
            'dash_back'   => $dashBack,
            'form'        => $form,
            'errors'      => $errors,
        ]);
    }

    public static function confermaPromozione(
        array $promoCreata,
        array $entita,
        int $entitaId,
        array $promozioni,
        string $tipoEntita,
        string $ruolo,
        string $dashBack
    ): void {
        self::render('promozioni/promozioni_servizio.tpl', 'Promozione creata', null, [
            'promo'         => $promozioni,
            'entita'        => $entita,
            'entita_id'     => $entitaId,
            'tipo_entita'   => $tipoEntita,
            'ruolo'         => $ruolo,
            'dash_back'     => $dashBack,
            'empty_list'    => $promozioni === [],
            'conferma_ok'   => true,
            'promo_creata'  => $promoCreata,
        ]);
    }
}
