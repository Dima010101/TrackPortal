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
    public function getAliquotaIva(): float            { return $this->aliquotaIva; }
    public function setAliquotaIva(float $v): void     { $this->aliquotaIva = $v; }
    public function getFiscDenominazione(): string     { return $this->fiscDenominazione; }
    public function setFiscDenominazione(string $v): void { $this->fiscDenominazione = $v; }
    public function getFiscPartitaIva(): string        { return $this->fiscPartitaIva; }
    public function setFiscPartitaIva(string $v): void { $this->fiscPartitaIva = $v; }
    public function getFiscCodiceFiscale(): ?string    { return $this->fiscCodiceFiscale; }
    public function setFiscCodiceFiscale(?string $v): void { $this->fiscCodiceFiscale = $v; }
    public function getFiscIndirizzo(): string         { return $this->fiscIndirizzo; }
    public function setFiscIndirizzo(string $v): void  { $this->fiscIndirizzo = $v; }
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

    /**
     * Commissione della piattaforma su un ammontare di ricavi, data la
     * percentuale di commissione.
     */
    public static function calcolaCommissione(float $ricavi, float $percentuale): float
    {
        return round($ricavi * $percentuale / 100, 2);
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