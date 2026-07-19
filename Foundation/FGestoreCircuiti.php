<?php

// repository gestore circuiti
class FGestoreCircuiti extends FAffiliato
{
    protected static function entityClass(): string
    {
        return EGestoreCircuiti::class;
    }

    protected static function tabellaProfilo(): string
    {
        return 'gestore_circuiti';
    }
}
