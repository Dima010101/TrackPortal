<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Prenotazione pilota su un circuito.
 */
#[ORM\Entity]
#[ORM\Table(name: 'prenotazione')]
class EPrenotazione
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'codice_identificativo', type: 'string', length: 32, unique: true)]
    protected string $codiceIdentificativo;

    #[ORM\Column(name: 'pilota_id', type: 'integer')]
    protected int $pilotaId;

    #[ORM\Column(name: 'circuito_id', type: 'integer')]
    protected int $circuitoId;

    #[ORM\Column(name: 'veicolo_noleggio_id', type: 'integer', nullable: true)]
    protected ?int $veicoloNoleggioId = null;

    #[ORM\Column(name: 'targa_veicolo', type: 'string', length: 20, nullable: true)]
    protected ?string $targaVeicolo = null;

    #[ORM\Column(name: 'inizio_disponibilita', type: 'string', length: 19)]
    protected string $inizioDisponibilita;

    #[ORM\Column(name: 'fine_disponibilita', type: 'string', length: 19)]
    protected string $fineDisponibilita;

    #[ORM\Column(name: 'assicurazione', type: 'boolean')]
    protected bool $assicurazione;

    #[ORM\Column(name: 'prezzo_id', type: 'integer', nullable: true)]
    protected ?int $prezzoId = null;

    #[ORM\Column(name: 'prezzo_importo', type: 'decimal', precision: 10, scale: 2)]
    protected float $prezzoImporto;

    #[ORM\Column(name: 'prezzo_valuta', type: 'string', length: 3)]
    protected string $prezzoValuta;

    #[ORM\Column(name: 'stato', type: 'string', length: 20)]
    protected string $stato;

    #[ORM\Column(name: 'carta_credito_id', type: 'integer')]
    protected int $cartaCreditoId;

    #[ORM\Column(name: 'promozione_id', type: 'integer', nullable: true)]
    protected ?int $promozioneId = null;

    #[ORM\Column(name: 'sconto_importo', type: 'decimal', precision: 10, scale: 2)]
    protected float $scontoImporto;

    #[ORM\Column(name: 'rimborso_previsto', type: 'decimal', precision: 10, scale: 2)]
    protected float $rimborsoPrevisto;

    #[ORM\Column(name: 'causa_cancellazione', type: 'string', length: 255, nullable: true)]
    protected ?string $causaCancellazione = null;

    #[ORM\Column(name: 'data_inserimento', type: 'string', length: 19, nullable: true)]
    protected ?string $dataInserimento = null;

    public function __construct(
        int $pilotaId = 0,
        int $circuitoId = 0,
        string $inizioDisponibilita = '',
        string $fineDisponibilita = '',
        ?int $veicoloNoleggioId = null,
        ?string $targaVeicolo = null,
        bool $assicurazione = false,
        ?int $prezzoId = null,
        float $prezzoImporto = 0.0,
        string $prezzoValuta = 'EUR',
        string $stato = 'in_attesa',
        string $codiceIdentificativo = '',
        int $cartaCreditoId = 0,
        ?int $promozioneId = null,
        float $scontoImporto = 0.0,
        float $rimborsoPrevisto = 0.0,
        ?string $causaCancellazione = null,
        ?int $id = null,
        ?string $dataInserimento = null
    ) {
        $this->id                    = $id;
        $this->codiceIdentificativo  = $codiceIdentificativo;
        $this->pilotaId              = $pilotaId;
        $this->circuitoId            = $circuitoId;
        $this->veicoloNoleggioId     = $veicoloNoleggioId;
        $this->targaVeicolo          = $targaVeicolo;
        $this->inizioDisponibilita   = $inizioDisponibilita;
        $this->fineDisponibilita     = $fineDisponibilita;
        $this->assicurazione         = $assicurazione;
        $this->prezzoId              = $prezzoId;
        $this->prezzoImporto         = $prezzoImporto;
        $this->prezzoValuta          = $prezzoValuta;
        $this->stato                 = $stato;
        $this->cartaCreditoId        = $cartaCreditoId;
        $this->promozioneId          = $promozioneId;
        $this->scontoImporto         = $scontoImporto;
        $this->rimborsoPrevisto      = $rimborsoPrevisto;
        $this->causaCancellazione    = $causaCancellazione;
        $this->dataInserimento       = $dataInserimento
            ?? (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['pilota_id'] ?? 0),
            (int)($r['circuito_id'] ?? 0),
            (string)($r['inizio_disponibilita'] ?? ''),
            (string)($r['fine_disponibilita'] ?? ''),
            isset($r['veicolo_noleggio_id']) && $r['veicolo_noleggio_id'] !== null
                ? (int)$r['veicolo_noleggio_id'] : null,
            $r['targa_veicolo'] ?? null,
            (bool)($r['assicurazione'] ?? false),
            isset($r['prezzo_id']) && $r['prezzo_id'] !== null ? (int)$r['prezzo_id'] : null,
            (float)($r['prezzo_importo'] ?? 0.0),
            (string)($r['prezzo_valuta'] ?? 'EUR'),
            (string)($r['stato'] ?? 'in_attesa'),
            (string)($r['codice_identificativo'] ?? ''),
            (int)($r['carta_credito_id'] ?? 0),
            isset($r['promozione_id']) && $r['promozione_id'] !== null ? (int)$r['promozione_id'] : null,
            (float)($r['sconto_importo'] ?? 0.0),
            (float)($r['rimborso_previsto'] ?? 0.0),
            $r['causa_cancellazione'] ?? null,
            isset($r['id']) ? (int)$r['id'] : null,
            $r['data_inserimento'] ?? null
        );
    }

    public function getId(): ?int                 { return $this->id; }
    public function getCodiceIdentificativo(): string { return $this->codiceIdentificativo; }
    public function getPilotaId(): int             { return $this->pilotaId; }
    public function getCircuitoId(): int         { return $this->circuitoId; }
    public function getVeicoloNoleggioId(): ?int { return $this->veicoloNoleggioId; }
    public function getTargaVeicolo(): ?string   { return $this->targaVeicolo; }
    public function getInizioDisponibilita(): string { return $this->inizioDisponibilita; }
    public function getFineDisponibilita(): string   { return $this->fineDisponibilita; }
    public function isAssicurazione(): bool      { return $this->assicurazione; }
    public function getPrezzoId(): ?int          { return $this->prezzoId; }
    public function getPrezzoImporto(): float    { return $this->prezzoImporto; }
    public function getPrezzoValuta(): string     { return $this->prezzoValuta; }
    public function getStato(): string           { return $this->stato; }
    public function setStato(string $v): void   { $this->stato = $v; }
    public function getCartaCreditoId(): int     { return $this->cartaCreditoId; }
    public function getPromozioneId(): ?int      { return $this->promozioneId; }
    public function getScontoImporto(): float    { return $this->scontoImporto; }
    public function getRimborsoPrevisto(): float { return $this->rimborsoPrevisto; }
    public function setRimborsoPrevisto(float $v): void { $this->rimborsoPrevisto = $v; }
    public function getCausaCancellazione(): ?string { return $this->causaCancellazione; }
    public function setCausaCancellazione(?string $v): void { $this->causaCancellazione = $v; }
    public function getDataInserimento(): ?string { return $this->dataInserimento; }
}
