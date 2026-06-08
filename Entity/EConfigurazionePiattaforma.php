<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Configurazione globale della piattaforma (riga singleton id=1).
 * Validazione e regole di business.
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

    public static function fromArray(array $r): self
    {
        return new self(
            (float)($r['prezzo_assicurazione'] ?? 0),
            (float)($r['percentuale_commissione'] ?? 0),
            (int)($r['id'] ?? self::ID_SISTEMA),
            $r['aggiornato_il'] ?? null
        );
    }

    public function getId(): int                       { return $this->id; }
    public function getPrezzoAssicurazione(): float    { return $this->prezzoAssicurazione; }
    public function getPercentualeCommissione(): float { return $this->percentualeCommissione; }
    public function getAggiornatoIl(): ?string         { return $this->aggiornatoIl; }

    public function setAggiornatoIl(string $v): void
    {
        $this->aggiornatoIl = $v;
    }

    public function setPrezzoAssicurazione(float $v): void
    {
        $this->prezzoAssicurazione = $v;
    }

    public function setPercentualeCommissione(float $v): void
    {
        $this->percentualeCommissione = $v;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'                       => $this->id,
            'prezzo_assicurazione'     => $this->prezzoAssicurazione,
            'percentuale_commissione'  => $this->percentualeCommissione,
            'aggiornato_il'            => $this->aggiornatoIl,
        ];
    }
}
