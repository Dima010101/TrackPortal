<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * valori attuali dei parametri della piattaforma (prezzo assicurazione, percentuale commissione e aliquota iva) .
 * Validazione e regole di business in FConfigurazionePiattaforma.
 */
#[ORM\Entity]
#[ORM\Table(name: 'configurazione_piattaforma')]
class EConfigurazionePiattaforma
{
    public const ID_SISTEMA = 1;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected int $id = self::ID_SISTEMA;

    #[ORM\Column(name: 'prezzo_assicurazione', type: 'decimal', precision: 10, scale: 2)]
    protected float $prezzoAssicurazione;

    #[ORM\Column(name: 'percentuale_commissione', type: 'decimal', precision: 5, scale: 2)]
    protected float $percentualeCommissione;

    #[ORM\Column(name: 'aliquota_iva', type: 'decimal', precision: 5, scale: 2)]
    protected float $aliquotaIva = 22.0;

    #[ORM\Column(name: 'fisc_denominazione', type: 'string', length: 190)]
    protected string $fiscDenominazione = 'TrackPortal';

    #[ORM\Column(name: 'fisc_partita_iva', type: 'string', length: 20)]
    protected string $fiscPartitaIva = '';

    #[ORM\Column(name: 'fisc_codice_fiscale', type: 'string', length: 20, nullable: true)]
    protected ?string $fiscCodiceFiscale = null;

    #[ORM\Column(name: 'fisc_indirizzo', type: 'string', length: 255)]
    protected string $fiscIndirizzo = '';

    #[ORM\Column(name: 'aggiornato_il', type: 'string', length: 19, nullable: true)]
    protected ?string $aggiornatoIl = null;

    public function __construct(
        float $prezzoAssicurazione = 25.0,
        float $percentualeCommissione = 10.0,
        int $id = self::ID_SISTEMA,
        ?string $aggiornatoIl = null
    ) {
        $this->id                      = $id;
        $this->prezzoAssicurazione     = $prezzoAssicurazione;
        $this->percentualeCommissione  = $percentualeCommissione;
        $this->aggiornatoIl            = $aggiornatoIl
            ?? (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }