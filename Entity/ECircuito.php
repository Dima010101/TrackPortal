<?php

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

<<<<<<< HEAD
*/*
 * ECircuito — entità per i circuiti gestiti dai gestori di circuiti.
=======
/**
 * ECircuito — autodromo + attributi di dominio.
>>>>>>> fcbccc295d5fa3291f88f0f44178402e2fe39a75
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

    #[ORM\Column(name: 'tariffa_accesso', type: 'decimal', precision: 10, scale: 2)]
    protected float $tariffaAccesso;

    #[ORM\Column(name: 'descrizione', type: 'text', nullable: true)]
    protected ?string $descrizione = null;

    #[ORM\Column(name: 'tipologia_veicoli', type: 'string', length: 20)]
    protected string $tipologiaVeicoli;

    #[ORM\Column(name: 'numero_box', type: 'integer')]
    protected int $numeroBox;

    #[ORM\Column(name: 'data_creazione', type: 'string', length: 19, nullable: true)]
    protected ?string $dataCreazione = null;

    /** @var Collection<int, CircuitoFoto> */
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
        ?float $lunghezzaKm = null, float $tariffaAccesso = 0.0,
        ?string $descrizione = null, string $tipologiaVeicoli = 'entrambi',
        int $numeroBox = 0,
        ?int $id = null, ?string $dataCreazione = null
    ) {
        $this->id               = $id;
        $this->gestoreId        = $gestoreId;
        $this->nomeCircuito     = $nomeCircuito;
        $this->localita         = $localita;
        $this->indirizzo        = $indirizzo;
        $this->lunghezzaKm      = $lunghezzaKm;
        $this->tariffaAccesso   = $tariffaAccesso;
        $this->descrizione      = $descrizione;
        $this->tipologiaVeicoli = $tipologiaVeicoli;
        $this->numeroBox        = $numeroBox;
        $this->dataCreazione    = $dataCreazione;
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
            (float)($r['tariffa_accesso'] ?? 0.0),
            $r['descrizione'] ?? null,
            (string)($r['tipologia_veicoli'] ?? 'entrambi'),
            (int)($r['numero_box'] ?? 0),
            isset($r['id']) ? (int)$r['id'] : null,
            $r['data_creazione'] ?? null
        );

        if (!empty($r['foto']) && is_array($r['foto'])) {
            $circuito->syncFotoFromRows($r['foto']);
        }

        return $circuito;
    }

    public function getId(): ?int                 { return $this->id; }
    public function setId(?int $v): void          { $this->id = $v; }
    public function getGestoreId(): int            { return $this->gestoreId; }
    public function setGestoreId(int $v): void     { $this->gestoreId = $v; }
    public function getNomeCircuito(): string      { return $this->nomeCircuito; }
    public function setNomeCircuito(string $v): void { $this->nomeCircuito = $v; }
    public function getLocalita(): string          { return $this->localita; }
    public function setLocalita(string $v): void   { $this->localita = $v; }
    public function getIndirizzo(): string         { return $this->indirizzo; }
    public function setIndirizzo(string $v): void  { $this->indirizzo = $v; }
    public function getLunghezzaKm(): ?float       { return $this->lunghezzaKm; }
    public function setLunghezzaKm(?float $v): void { $this->lunghezzaKm = $v; }
    public function getTariffaAccesso(): float      { return $this->tariffaAccesso; }
    public function setTariffaAccesso(float $v): void { $this->tariffaAccesso = $v; }
    public function getDescrizione(): ?string      { return $this->descrizione; }
    public function setDescrizione(?string $v): void { $this->descrizione = $v; }
    public function getTipologiaVeicoli(): string  { return $this->tipologiaVeicoli; }
    public function setTipologiaVeicoli(string $v): void { $this->tipologiaVeicoli = $v; }
    public function getNumeroBox(): int            { return $this->numeroBox; }
    public function setNumeroBox(int $v): void     { $this->numeroBox = $v; }
    public function getDataCreazione(): ?string    { return $this->dataCreazione; }

    /** @return Collection<int, CircuitoFoto> */
    public function getFoto(): Collection
    {
        return $this->foto;
    }

    /** @return list<CircuitoFoto> */
    public function getFotoOrdinate(): array
    {
        return $this->foto->toArray();
    }

    public function haFoto(): bool
    {
        return !$this->foto->isEmpty();
    }

    public function contaFoto(): int
    {
        return $this->foto->count();
    }

    public function getCopertinaPath(): ?string
    {
        $prima = $this->foto->first();
        if ($prima instanceof CircuitoFoto) {
            return $prima->getPathFile();
        }

        return null;
    }

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

    public function removeFotoById(int $fotoId): bool
    {
        foreach ($this->foto as $foto) {
            if ($foto->getId() === $fotoId) {
                $this->removeFoto($foto);
                return true;
            }
        }
        return false;
    }

    public function removeFotoByPath(string $pathFile): bool
    {
        foreach ($this->foto as $foto) {
            if ($foto->getPathFile() === $pathFile) {
                $this->removeFoto($foto);
                return true;
            }
        }
        return false;
    }

    public function clearFoto(): void
    {
        $this->foto->clear();
    }

    /**
     * Sostituisce l'intera galleria con l'elenco di path (ordine = indice array).
     *
     * @param list<string> $paths
     */
    public function setFotoPaths(array $paths): void
    {
        $this->clearFoto();
        foreach (array_values($paths) as $i => $path) {
            if (is_string($path) && trim($path) !== '') {
                $this->addFoto($path, null, $i);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $rows Righe da query SQL (id, path_file, ordine, didascalia)
     */
    public function syncFotoFromRows(array $rows): void
    {
        $this->clearFoto();
        foreach ($rows as $row) {
            $path = (string)($row['path_file'] ?? '');
            if ($path === '') {
                continue;
            }
            $foto = new CircuitoFoto(
                $this,
                $path,
                isset($row['didascalia']) ? (string)$row['didascalia'] : null,
                (int)($row['ordine'] ?? $this->foto->count()),
                isset($row['id']) ? (int)$row['id'] : null
            );
            $this->foto->add($foto);
        }
    }

    /** @return list<array{id: ?int, path_file: string, ordine: int, didascalia: ?string}> */
    public function fotoToArray(): array
    {
        $out = [];
        foreach ($this->getFotoOrdinate() as $foto) {
            $out[] = $foto->toArray();
        }
        return $out;
    }

    /** @return list<string> */
    public function getFotoPaths(): array
    {
        return array_map(
            static fn (CircuitoFoto $f): string => $f->getPathFile(),
            $this->getFotoOrdinate()
        );
    }
}

/**
 * Foto di un circuito — entità figlia dell'aggregato {@see ECircuito}.
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

    public function getId(): ?int              { return $this->id; }
    public function getCircuito(): ECircuito   { return $this->circuito; }
    public function getPathFile(): string      { return $this->pathFile; }
    public function setPathFile(string $v): void { $this->pathFile = $v; }
    public function getOrdine(): int           { return $this->ordine; }
    public function setOrdine(int $v): void    { $this->ordine = $v; }
    public function getDidascalia(): ?string   { return $this->didascalia; }
    public function setDidascalia(?string $v): void { $this->didascalia = $v; }

    /** @return array{id: ?int, path_file: string, ordine: int, didascalia: ?string} */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'path_file'  => $this->pathFile,
            'ordine'     => $this->ordine,
            'didascalia' => $this->didascalia,
        ];
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> fcbccc295d5fa3291f88f0f44178402e2fe39a75
