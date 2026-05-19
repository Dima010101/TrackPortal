<?php


/**
 * EPrenotazione — prenotazione pilota su circuito con fascia oraria (modello UML).
 */

class EPrenotazione
{
    protected ?int $id;
    protected string $codiceIdentificativo;
    protected int $pilotaId;
    protected int $circuitoId;
    protected ?int $veicoloNoleggioId;
    protected ?string $targaVeicolo;
    protected string $inizioDisponibilita;
    protected string $fineDisponibilita;
    protected bool $assicurazione;
    protected ?int $prezzoId;
    protected float $prezzoImporto;
    protected string $prezzoValuta;
    protected string $stato;
    protected int $cartaCreditoId;
    protected float $rimborsoPrevisto;
    protected ?string $causaCancellazione;
    protected ?string $dataInserimento;
    
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
        float $rimborsoPrevisto = 0.0,
        ?string $causaCancellazione = null,
        ?int $id = null,
        ?string $dataInserimento = null
    ){
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
        $this->rimborsoPrevisto      = $rimborsoPrevisto;
        $this->causaCancellazione    = $causaCancellazione;
        $this->dataInserimento       = $dataInserimento;
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
    public function getRimborsoPrevisto(): float { return $this->rimborsoPrevisto; }
    public function setRimborsoPrevisto(float $v): void { $this->rimborsoPrevisto = $v; }
    public function getCausaCancellazione(): ?string { return $this->causaCancellazione; }
    public function setCausaCancellazione(?string $v): void { $this->causaCancellazione = $v; }
    public function getDataInserimento(): ?string { return $this->dataInserimento; }
}