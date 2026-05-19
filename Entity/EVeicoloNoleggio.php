<?php
/**
 * entità che rappresenta un veicolo noleggiabile; il listino è nella tabella `prezzo`.
 * In fase di `store`, `listinoImporto` / `listinoValuta` creano la prima voce di prezzo.
 */

class EVeicoloNoleggio
{
    protected ?int $id;
    protected int $aziendaId;
    protected int $circuitoId;
    protected string $targa;
    protected string $categoria;
    protected int $capienza;
    protected ?int $potenzaCv;
    protected ?int $anno;
    protected ?string $marca;
    protected ?string $modello;
    protected bool $disponibile;
    /** Usato solo durante il salvataggio (prima riga listino) */
    protected float $listinoImporto;
    protected string $listinoValuta;
}