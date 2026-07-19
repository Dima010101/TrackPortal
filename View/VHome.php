<?php

// homepage pubblica di TrackPortal, hero ed elenco circuiti
class VHome extends VBase
{
    public static function index(array $circuiti): void
    {
        self::render('home.tpl', (string) constant('APP_NAME'), null, [
            'circuiti' => $circuiti,
        ]);
    }
}
