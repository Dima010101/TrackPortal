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

    /** Metodi di costruzione e inizializzazione sia da 0 che da array*/
    public function __construct(
        int $aziendaId = 0, int $circuitoId = 0, string $targa = '',
        string $categoria = 'auto', int $capienza = 1, ?int $potenzaCv = null,
        ?int $anno = null, ?string $marca = null, ?string $modello = null,
        bool $disponibile = true, float $listinoImporto = 0.0,
        string $listinoValuta = 'EUR', ?int $id = null
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
        $this->disponibile   = $disponibile;
        $this->listinoImporto = $listinoImporto;
        $this->listinoValuta = $listinoValuta;
    }

    public static function fromArray(array $r): self
    {
        $importo = (float)($r['listino_importo'] ?? $r['prezzo_importo_listino'] ?? 0.0);
        $valuta  = (string)($r['listino_valuta'] ?? 'EUR');
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
            (bool)($r['disponibile'] ?? true),
            $importo,
            $valuta,
            isset($r['id']) ? (int)$r['id'] : null
        );
    }

    /** Metodi getter e setter */
    public function getId(): ?int { return $this->id; }
    public function getAziendaId(): int { return $this->aziendaId; }
    public function setAziendaId(int $v): void { $this->aziendaId = $v; }
    public function getCircuitoId(): int { return $this->circuitoId; }
    public function setCircuitoId(int $v): void { $this->circuitoId = $v; }
    public function getTarga(): string { return $this->targa; }
    public function setTarga(string $v): void { $this->targa = $v; }
    public function getCategoria(): string { return $this->categoria; }
    public function setCategoria(string $v): void { $this->categoria = $v; }
    public function getCapienza(): int { return $this->capienza; }
    public function setCapienza(int $v): void { $this->capienza = $v; }
    public function getPotenzaCv(): ?int { return $this->potenzaCv; }
    public function setPotenzaCv(?int $v): void { $this->potenzaCv = $v; }
    public function getAnno(): ?int { return $this->anno; }
    public function setAnno(?int $v): void { $this->anno = $v; }
    public function getMarca(): ?string { return $this->marca; }
    public function setMarca(?string $v): void { $this->marca = $v; }
    public function getModello(): ?string { return $this->modello; }
    public function setModello(?string $v): void { $this->modello = $v; }
    public function getDisponibile(): bool { return $this->disponibile; }
    public function setDisponibile(bool $v): void { $this->disponibile = $v; }
    public function getListinoImporto(): float { return $this->listinoImporto; }
    public function getListinoValuta(): string { return $this->listinoValuta; }
}