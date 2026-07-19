<?php

// repository azienda di noleggio
class FGestoreNoleggio extends FAffiliato
{
    protected static function entityClass(): string
    {
        return EGestoreNoleggio::class;
    }

    protected static function tabellaProfilo(): string
    {
        return 'azienda_noleggio';
    }
}
