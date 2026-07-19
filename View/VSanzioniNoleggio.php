<?php

/**
 * VSanzioniNoleggio — view dell'area gestione piloti (ban / sospensioni)
 */
class VSanzioniNoleggio extends VBase
{
    
    public static function elenco(
        array $sanzioni,
        array $piloti,
        array $errors = [],
        array $form = []
    ): void {
        self::render('gestore_noleggio/sanzioni.tpl', 'Sanzioni', null, [
            'sanzioni'    => $sanzioni,
            'piloti'      => $piloti,
            'empty_list'  => $sanzioni === [],
            'can_add'     => $piloti !== [],
            'errors'      => $errors,
            'form'        => $form,
            'oggi'        => (new DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }
}
