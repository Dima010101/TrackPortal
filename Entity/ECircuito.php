<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entità che rappresenta un circuito associato a un gestore
 * Le foto sono gestite come aggregato interno
 */
#[ORM\Entity]
#[ORM\Table(name: 'circuito')]
class ECircuito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'gestore_id', type: 'integer')]
    protected int $gestoreId;

    #[ORM\Column(name: 'nome_circuito', type: 'string', length: 150)]
    protected string $nomeCircuito;

    #[ORM\Column(name: 'localita', type: 'string', length: 255)]
    protected string $localita;

    #[ORM\Column(name: 'indirizzo', type: 'string', length: 255)]
    protected string $indirizzo;

    #[ORM\Column(name: 'lunghezza_km', type: 'decimal', precision: 10, scale: 3, nullable: true)]
    protected ?float $lunghezzaKm = null;

    #[ORM\Column(name: 'descrizione', type: 'text', nullable: true)]
    protected ?string $descrizione = null;

    #[ORM\Column(name: 'tipologia_veicoli', type: 'string', length: 20)]
    protected string $tipologiaVeicoli;

    #[ORM\Column(name: 'numero_box', type: 'integer')]
    protected int $numeroBox;

    #[ORM\Column(name: 'telefono', type: 'string', length: 30, nullable: true)]
    protected ?string $telefono = null;

    #[ORM\Column(name: 'email', type: 'string', length: 190, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'sito_web', type: 'string', length: 255, nullable: true)]
    protected ?string $sitoWeb = null;

    #[ORM\Column(name: 'data_creazione', type: 'string', length: 19, nullable: true)]
    protected ?string $dataCreazione = null;

    /** relazione circuito e circuitofoto*/
    #[ORM\OneToMany(
        mappedBy: 'circuito',
        targetEntity: CircuitoFoto::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['ordine' => 'ASC', 'id' => 'ASC'])]
    protected Collection $foto;

    public function __construct(
        int $gestoreId = 0, string $nomeCircuito = '',
        string $localita = '', string $indirizzo = '',
        ?float $lunghezzaKm = null,
        ?string $descrizione = null, string $tipologiaVeicoli = 'entrambi',
        int $numeroBox = 0,
        ?string $telefono = null, ?string $email = null, ?string $sitoWeb = null,
        ?int $id = null, ?string $dataCreazione = null
    ) {
        $this->id               = $id;
        $this->gestoreId        = $gestoreId;
        $this->nomeCircuito     = $nomeCircuito;
        $this->localita         = $localita;
        $this->indirizzo        = $indirizzo;
        $this->lunghezzaKm      = $lunghezzaKm;
        $this->descrizione      = $descrizione;
        $this->tipologiaVeicoli = $tipologiaVeicoli;
        $this->numeroBox        = $numeroBox;
        $this->telefono         = $telefono;
        $this->email            = $email;
        $this->sitoWeb          = $sitoWeb;
        $this->dataCreazione    = $dataCreazione ?? ($id === null
            ? (new DateTimeImmutable())->format('Y-m-d H:i:s')
            : null);
        $this->foto             = new ArrayCollection();
    }

    public static function fromArray(array $r): self
    {
        $circuito = new self(
            (int)($r['gestore_id'] ?? 0),
            (string)($r['nome_circuito'] ?? ''),
            (string)($r['localita'] ?? ''),
            (string)($r['indirizzo'] ?? ''),
            isset($r['lunghezza_km']) && $r['lunghezza_km'] !== null ? (float)$r['lunghezza_km'] : null,
            $r['descrizione'] ?? null,
            (string)($r['tipologia_veicoli'] ?? 'entrambi'),
            (int)($r['numero_box'] ?? 0),
            $r['telefono'] ?? null,
            $r['email'] ?? null,
            $r['sito_web'] ?? null,
            isset($r['id']) ? (int)$r['id'] : null,
            $r['data_creazione'] ?? null
        );

        return $circuito;
    }

   public function getId(): ?int { return $this->id; }
    public function setId(?int $v): void { $this->id = $v; }
    public function getGestoreId(): int { return $this->gestoreId; }
    public function setGestoreId(int $v): void { $this->gestoreId = $v; }
    public function getNomeCircuito(): string { return $this->nomeCircuito; }
    public function setNomeCircuito(string $v): void { $this->nomeCircuito = $v; }
    public function getLocalita(): string { return $this->localita; }
    public function setLocalita(string $v): void { $this->localita = $v; }
    public function getIndirizzo(): string { return $this->indirizzo; }
    public function setIndirizzo(string $v): void { $this->indirizzo = $v; }
    public function getLunghezzaKm(): ?float { return $this->lunghezzaKm; }
    public function setLunghezzaKm(?float $v): void { $this->lunghezzaKm = $v; }
    public function getDescrizione(): ?string { return $this->descrizione; }
    public function setDescrizione(?string $v): void { $this->descrizione = $v; }
    public function getTipologiaVeicoli(): string { return $this->tipologiaVeicoli; }
    public function setTipologiaVeicoli(string $v): void { $this->tipologiaVeicoli = $v; }
    public function getNumeroBox(): int            { return $this->numeroBox; }
    public function setNumeroBox(int $v): void     { $this->numeroBox = $v; }
    public function getTelefono(): ?string         { return $this->telefono; }
    public function setTelefono(?string $v): void  { $this->telefono = $v; }
    public function getEmail(): ?string            { return $this->email; }
    public function setEmail(?string $v): void    { $this->email = $v; }
    public function getSitoWeb(): ?string         { return $this->sitoWeb; }
    public function setSitoWeb(?string $v): void  { $this->sitoWeb = $v; }
    public function getDataCreazione(): ?string    { return $this->dataCreazione; }

    /**
     * metodo che restituisce true se la tipologia di circuito ammette la categoria del veicolo
     */
    public static function ammetteCategoria(string $tipologiaCircuito, string $categoriaVeicolo): bool
    {
        if ($tipologiaCircuito === 'auto' && $categoriaVeicolo === 'moto') {
            return false;
        }
        if ($tipologiaCircuito === 'moto' && $categoriaVeicolo === 'auto') {
            return false;
        }

        return true;
    }

    /** metodi della classe circuitofoto*/
    public function getFotoOrdinate(): array
    {
        return $this->foto->toArray();
    }

    public function contaFoto(): int
    {
        return $this->foto->count();
    }

    /** assegno il path alla foto o se gia esiste aggiorno quella esistente*/
    public function addFoto(string $pathFile, ?string $didascalia = null, ?int $ordine = null): CircuitoFoto
    {
        $pathFile = trim($pathFile);
        if ($pathFile === '') {
            throw new InvalidArgumentException('Il percorso della foto non può essere vuoto.');
        }

        foreach ($this->foto as $esistente) {
            if ($esistente->getPathFile() === $pathFile) {
                if ($didascalia !== null) {
                    $esistente->setDidascalia($didascalia);
                }
                if ($ordine !== null) {
                    $esistente->setOrdine($ordine);
                }
                return $esistente;
            }
        }

        $foto = new CircuitoFoto($this, $pathFile, $didascalia, $ordine ?? $this->foto->count());
        $this->foto->add($foto);

        return $foto;
    }

    public function removeFoto(CircuitoFoto $foto): void
    {
        if ($foto->getCircuito() !== $this) {
            return;
        }
        $this->foto->removeElement($foto);
    }

    /** restituisce lista delle foto*/
    public function fotoToArray(): array
    {
        $out = [];
        foreach ($this->getFotoOrdinate() as $foto) {
            $out[] = $foto->toArray();
        }
        return $out;
    }
}

/**
 * entità figlia circuito foto.
 */
#[ORM\Entity]
#[ORM\Table(name: 'circuito_foto')]
class CircuitoFoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ECircuito::class, inversedBy: 'foto')]
    #[ORM\JoinColumn(name: 'circuito_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ECircuito $circuito;

    #[ORM\Column(name: 'path_file', type: 'string', length: 255)]
    protected string $pathFile;

    #[ORM\Column(name: 'ordine', type: 'integer')]
    protected int $ordine = 0;

    #[ORM\Column(name: 'didascalia', type: 'string', length: 255, nullable: true)]
    protected ?string $didascalia = null;

    public function __construct(
        ECircuito $circuito,
        string $pathFile,
        ?string $didascalia = null,
        int $ordine = 0,
        ?int $id = null
    ) {
        $this->circuito   = $circuito;
        $this->pathFile   = $pathFile;
        $this->didascalia = $didascalia;
        $this->ordine     = $ordine;
        $this->id         = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getCircuito(): ECircuito { return $this->circuito; }
    public function getPathFile(): string { return $this->pathFile; }
    public function setPathFile(string $v): void { $this->pathFile = $v; }
    public function getOrdine(): int { return $this->ordine; }
    public function setOrdine(int $v): void { $this->ordine = $v; }
    public function getDidascalia(): ?string { return $this->didascalia; }
    public function setDidascalia(?string $v): void { $this->didascalia = $v; }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'path_file'  => $this->pathFile,
            'ordine'     => $this->ordine,
            'didascalia' => $this->didascalia,
        ];
    }
}
