<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * EVeicoloNoleggio — veicolo noleggiabile
 */
#[ORM\Entity]
#[ORM\Table(name: 'veicolo_noleggio')]
class EVeicoloNoleggio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'azienda_id', type: 'integer')]
    protected int $aziendaId;

    #[ORM\Column(name: 'circuito_id', type: 'integer')]
    protected int $circuitoId;

    #[ORM\Column(name: 'targa', type: 'string', length: 20, unique: true)]
    protected string $targa;

    #[ORM\Column(name: 'categoria', type: 'string', length: 10)]
    protected string $categoria;

    #[ORM\Column(name: 'capienza', type: 'integer')]
    protected int $capienza;

    #[ORM\Column(name: 'potenza_cv', type: 'integer', nullable: true)]
    protected ?int $potenzaCv = null;

    #[ORM\Column(name: 'anno', type: 'integer', nullable: true)]
    protected ?int $anno = null;

    #[ORM\Column(name: 'marca', type: 'string', length: 80, nullable: true)]
    protected ?string $marca = null;

    #[ORM\Column(name: 'modello', type: 'string', length: 80, nullable: true)]
    protected ?string $modello = null;

    #[ORM\Column(name: 'prezzo', type: 'decimal', precision: 10, scale: 2)]
    protected float $prezzo = 0.0;

    #[ORM\Column(name: 'disponibile', type: 'boolean')]
    protected bool $disponibile;

    public function __construct(
        int $aziendaId = 0, int $circuitoId = 0, string $targa = '',
        string $categoria = 'auto', int $capienza = 1, ?int $potenzaCv = null,
        ?int $anno = null, ?string $marca = null, ?string $modello = null,
        float $prezzo = 0.0, bool $disponibile = true, ?int $id = null
    ) {
        $this->id            = $id;
        $this->aziendaId     = $aziendaId;
        $this->circuitoId    = $circuitoId;
        $this->targa         = $targa;
        $this->categoria     = $categoria;
        $this->capienza      = $capienza;
        $this->potenzaCv     = $potenzaCv;
        $this->anno          = $anno;
        $this->marca         = $marca;
        $this->modello       = $modello;
        $this->prezzo        = max(0.0, $prezzo);
        $this->disponibile   = $disponibile;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['azienda_id'] ?? 0),
            (int)($r['circuito_id'] ?? 0),
            (string)($r['targa'] ?? ''),
            (string)($r['categoria'] ?? 'auto'),
            (int)($r['capienza'] ?? 1),
            isset($r['potenza_cv']) ? (int)$r['potenza_cv'] : null,
            isset($r['anno']) ? (int)$r['anno'] : null,
            $r['marca'] ?? null,
            $r['modello'] ?? null,
            (float)($r['prezzo'] ?? 0.0),
            (bool)($r['disponibile'] ?? true),
            isset($r['id']) ? (int)$r['id'] : null
        );
    }

    public function getId(): ?int             { return $this->id; }
    public function setId(?int $v): void       { $this->id = $v; }
    public function getAziendaId(): int       { return $this->aziendaId; }
    public function setAziendaId(int $v): void { $this->aziendaId = $v; }
    public function getCircuitoId(): int      { return $this->circuitoId; }
    public function setCircuitoId(int $v): void { $this->circuitoId = $v; }
    public function getTarga(): string       { return $this->targa; }
    public function setTarga(string $v): void { $this->targa = $v; }
    public function getCategoria(): string   { return $this->categoria; }
    public function setCategoria(string $v): void { $this->categoria = $v; }
    public function getCapienza(): int       { return $this->capienza; }
    public function setCapienza(int $v): void { $this->capienza = $v; }
    public function getPotenzaCv(): ?int     { return $this->potenzaCv; }
    public function setPotenzaCv(?int $v): void { $this->potenzaCv = $v; }
    public function getAnno(): ?int          { return $this->anno; }
    public function setAnno(?int $v): void   { $this->anno = $v; }
    public function getMarca(): ?string      { return $this->marca; }
    public function setMarca(?string $v): void { $this->marca = $v; }
    public function getModello(): ?string    { return $this->modello; }
    public function setModello(?string $v): void { $this->modello = $v; }
    public function getPrezzo(): float       { return $this->prezzo; }
    public function setPrezzo(float $v): void { $this->prezzo = max(0.0, $v); }
    public function isDisponibile(): bool    { return $this->disponibile; }
    public function setDisponibile(bool $v): void { $this->disponibile = $v; }
}
