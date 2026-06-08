<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Carta di credito associata al pilota.
 */
#[ORM\Entity]
#[ORM\Table(name: 'carta_credito')]
class ECartaCredito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'pilota_id', type: 'integer')]
    protected int $pilotaId;

    #[ORM\Column(name: 'nome_titolare', type: 'string', length: 80)]
    protected string $nomeTitolare;

    #[ORM\Column(name: 'cognome_titolare', type: 'string', length: 80)]
    protected string $cognomeTitolare;

    #[ORM\Column(name: 'numero_masked', type: 'string', length: 20)]
    protected string $numeroMasked;

    #[ORM\Column(name: 'data_scadenza', type: 'string', length: 7)]
    protected string $dataScadenza;

    #[ORM\Column(name: 'cvv_hash', type: 'string', length: 255)]
    protected string $cvvHash;

    public function __construct(
        int $pilotaId = 0, string $nomeTitolare = '', string $cognomeTitolare = '',
        string $numeroMasked = '', string $dataScadenza = '', string $cvvHash = '',
        ?int $id = null
    ) {
        $this->id              = $id;
        $this->pilotaId        = $pilotaId;
        $this->nomeTitolare    = $nomeTitolare;
        $this->cognomeTitolare = $cognomeTitolare;
        $this->numeroMasked    = $numeroMasked;
        $this->dataScadenza    = $dataScadenza;
        $this->cvvHash         = $cvvHash;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['pilota_id'] ?? 0),
            (string)($r['nome_titolare'] ?? ''),
            (string)($r['cognome_titolare'] ?? ''),
            (string)($r['numero_masked'] ?? ''),
            (string)($r['data_scadenza'] ?? ''),
            (string)($r['cvv_hash'] ?? ''),
            isset($r['id']) ? (int)$r['id'] : null
        );
    }

    public function getId(): ?int             { return $this->id; }
    public function getPilotaId(): int         { return $this->pilotaId; }
    public function getNomeTitolare(): string { return $this->nomeTitolare; }
    public function getCognomeTitolare(): string { return $this->cognomeTitolare; }
    public function getNumeroMasked(): string { return $this->numeroMasked; }
    public function getDataScadenza(): string { return $this->dataScadenza; }
    public function getCvvHash(): string      { return $this->cvvHash; }
}
