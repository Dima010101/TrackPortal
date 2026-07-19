<?php


/**
 * VAmministratore — view del pannello amministratore (commissioni, affiliazioni, fatture).
 */
class VAmministratore extends VBase
{
    
    public static function commissioni(
        float $prezzo,
        float $perc,
        float $aliquota,
        float $ricavoAssic,
        array $storia,
        array $grafici = [],
        bool $anteprima = false,
        array $errors = [],
        array $form = []
    ): void {
        self::render('amministratore/commissioni.tpl', 'Gestione commissioni', null, [
            'prezzo'       => $prezzo,
            'perc'         => $perc,
            'aliquota'     => $aliquota,
            'ricavo_assic' => $ricavoAssic,
            'storia'       => $storia,
            'grafici'      => $grafici,
            'anteprima'    => $anteprima,
            'errors'       => $errors,
            'form'         => $form,
        ]);
    }

    public static function commissioniRiepilogo(
        float $prezzoAttuale,
        float $percAttuale,
        float $prezzoNuovo,
        float $percNuova,
        float $ricavoAttuale,
        float $ricavoNuovo,
        float $aliquotaAttuale = 0.0,
        float $aliquotaNuova = 0.0
    ): void {
        self::render('amministratore/commissioni_riepilogo.tpl', 'Riepilogo modifica', null, [
            'prezzo_attuale'   => $prezzoAttuale,
            'perc_attuale'     => $percAttuale,
            'prezzo_nuovo'     => $prezzoNuovo,
            'perc_nuova'       => $percNuova,
            'ricavo_attuale'   => $ricavoAttuale,
            'ricavo_nuovo'     => $ricavoNuovo,
            'aliquota_attuale' => $aliquotaAttuale,
            'aliquota_nuova'   => $aliquotaNuova,
        ]);
    }

    public static function commissioniConferma(
        bool $success,
        array $errors = [],
        float $prezzo = 0.0,
        float $perc = 0.0,
        float $aliquota = 0.0
    ): void {
        self::render('amministratore/commissioni_conferma.tpl', 'Esito modifica', null, [
            'success'  => $success,
            'errors'   => $errors,
            'prezzo'   => $prezzo,
            'perc'     => $perc,
            'aliquota' => $aliquota,
        ]);
    }

    public static function fatture(array $ultime, array $template = []): void
    {
        self::render('amministratore/fatture.tpl', 'Fatturazione', null, [
            'ultime' => $ultime,
            'tpl'    => $template,
        ]);
    }

}
