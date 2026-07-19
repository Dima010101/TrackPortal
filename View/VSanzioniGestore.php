<?php

/**
 * VSanzioniGestore — view dell'area gestione piloti (ban / sospensioni) del gestore.
 */
class VSanzioniGestore extends VBase
{
    
    public static function elenco(
        array $sanzioni,
        array $piloti,
        array $errors = [],
        array $form = []
    ): void {
        self::render('gestore_circuiti/sanzioni.tpl', 'Sanzioni', null, [
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
