<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * EPrezzo — elemento di listino (importo, valuta) associato a un veicolo noleggiabile (UML).
 */
#[ORM\Entity]
#[ORM\Table(name: 'prezzo')]
class EPrezzo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'veicolo_noleggio_id', type: 'integer')]
    protected int $veicoloNoleggioId;

    #[ORM\Column(name: 'importo', type: 'decimal', precision: 10, scale: 2)]
    protected float $importo;

    #[ORM\Column(name: 'valuta', type: 'string', length: 3)]
    protected string $valuta;

    public function __construct(
        int $veicoloNoleggioId = 0,
        float $importo = 0.0,
        string $valuta = 'EUR',
        ?int $id = null
    ) {
        $this->id                 = $id;
        $this->veicoloNoleggioId  = $veicoloNoleggioId;
        $this->importo            = $importo;
        $this->valuta             = $valuta;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['veicolo_noleggio_id'] ?? 0),
            (float)($r['importo'] ?? 0.0),
            (string)($r['valuta'] ?? 'EUR'),
            isset($r['id']) ? (int)$r['id'] : null
        );
    }

    public function getId(): ?int              { return $this->id; }
    public function getVeicoloNoleggioId(): int { return $this->veicoloNoleggioId; }
    public function getImporto(): float        { return $this->importo; }
    public function getValuta(): string         { return $this->valuta; }
}
