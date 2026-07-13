<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Sanzione (ban o sospensione) inflitta da un EMITTENTE a un pilota,
 *
 * NON modifica lo stato_account globale del pilota (gestito dall'amministrazione)
 * né lo blocca sui servizi di altri emittenti: incide soltanto sulla possibilità
 * di prenotare i servizi dell'emittente che l'ha emessa.
 */
#[ORM\Entity]
#[ORM\Table(name: 'sanzione_pilota')]
class ESanzionePilota
{
    public const TIPO_BAN         = 'ban';
    public const TIPO_SOSPENSIONE = 'sospensione';

    public const STATO_ATTIVA   = 'attiva';
    public const STATO_REVOCATA = 'revocata';

    public const EMITTENTE_GESTORE = 'gestore_circuiti';
    public const EMITTENTE_AZIENDA = 'azienda_noleggio';

    /** @var list<string> */
    public const TIPI = [self::TIPO_BAN, self::TIPO_SOSPENSIONE];

    /** @var list<string> */
    public const EMITTENTI = [self::EMITTENTE_GESTORE, self::EMITTENTE_AZIENDA];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'emittente_tipo', type: 'string', length: 20)]
    protected string $emittenteTipo = self::EMITTENTE_GESTORE;

    #[ORM\Column(name: 'gestore_id', type: 'integer')]
    protected int $gestoreId;

    #[ORM\Column(name: 'pilota_id', type: 'integer')]
    protected int $pilotaId;

    #[ORM\Column(name: 'tipo', type: 'string', length: 20)]
    protected string $tipo;

    #[ORM\Column(name: 'motivo', type: 'string', length: 255, nullable: true)]
    protected ?string $motivo = null;

    #[ORM\Column(name: 'data_inizio', type: 'string', length: 10)]
    protected string $dataInizio;

    #[ORM\Column(name: 'data_fine', type: 'string', length: 10, nullable: true)]
    protected ?string $dataFine = null;

    #[ORM\Column(name: 'stato', type: 'string', length: 20)]
    protected string $stato = self::STATO_ATTIVA;

    #[ORM\Column(name: 'data_creazione', type: 'string', length: 19, nullable: true)]
    protected ?string $dataCreazione = null;

    #[ORM\Column(name: 'revocata_il', type: 'string', length: 19, nullable: true)]
    protected ?string $revocataIl = null;

    public function __construct(
        int $gestoreId = 0,
        int $pilotaId = 0,
        string $tipo = self::TIPO_BAN,
        ?string $dataFine = null,
        ?string $motivo = null,
        ?string $dataInizio = null,
        string $stato = self::STATO_ATTIVA,
        ?int $id = null,
        ?string $dataCreazione = null,
        string $emittenteTipo = self::EMITTENTE_GESTORE
    ) {
        $this->emittenteTipo = in_array($emittenteTipo, self::EMITTENTI, true)
            ? $emittenteTipo
            : self::EMITTENTE_GESTORE;
        $this->gestoreId  = $gestoreId;
        $this->pilotaId   = $pilotaId;
        $this->tipo       = $tipo;
        // Il ban è permanente: la data di fine non ha senso e viene azzerata.
        $this->dataFine   = $tipo === self::TIPO_BAN ? null : $dataFine;
        $this->motivo     = $motivo;
        $this->dataInizio = $dataInizio ?? (new DateTimeImmutable('today'))->format('Y-m-d');
        $this->stato      = $stato;
        $this->id         = $id;
        $this->dataCreazione = $dataCreazione ?? ($id === null
            ? (new DateTimeImmutable())->format('Y-m-d H:i:s')
            : null);
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int) ($r['gestore_id'] ?? 0),
            (int) ($r['pilota_id'] ?? 0),
            (string) ($r['tipo'] ?? self::TIPO_BAN),
            isset($r['data_fine']) && $r['data_fine'] !== null ? (string) $r['data_fine'] : null,
            isset($r['motivo']) && $r['motivo'] !== null ? (string) $r['motivo'] : null,
            isset($r['data_inizio']) ? (string) $r['data_inizio'] : null,
            (string) ($r['stato'] ?? self::STATO_ATTIVA),
            isset($r['id']) ? (int) $r['id'] : null,
            $r['data_creazione'] ?? null,
            (string) ($r['emittente_tipo'] ?? self::EMITTENTE_GESTORE)
        );
    }

    public function getId(): ?int                 { return $this->id; }
    public function getEmittenteTipo(): string     { return $this->emittenteTipo; }
    public function getGestoreId(): int            { return $this->gestoreId; }
    public function setGestoreId(int $v): void     { $this->gestoreId = $v; }
    public function getPilotaId(): int             { return $this->pilotaId; }
    public function setPilotaId(int $v): void      { $this->pilotaId = $v; }
    public function getTipo(): string              { return $this->tipo; }
    public function setTipo(string $v): void       { $this->tipo = $v; }
    public function getMotivo(): ?string           { return $this->motivo; }
    public function setMotivo(?string $v): void    { $this->motivo = $v; }
    public function getDataInizio(): string        { return $this->dataInizio; }
    public function setDataInizio(string $v): void { $this->dataInizio = $v; }
    public function getDataFine(): ?string         { return $this->dataFine; }
    public function setDataFine(?string $v): void  { $this->dataFine = $v; }
    public function getStato(): string             { return $this->stato; }
    public function setStato(string $v): void      { $this->stato = $v; }
    public function getDataCreazione(): ?string    { return $this->dataCreazione; }
    public function getRevocataIl(): ?string       { return $this->revocataIl; }

    public function isBan(): bool        { return $this->tipo === self::TIPO_BAN; }
    public function isAttiva(): bool     { return $this->stato === self::STATO_ATTIVA; }

    /** Revoca la sanzione: smette di bloccare il pilota da questo istante. */
    public function revoca(): void
    {
        $this->stato      = self::STATO_REVOCATA;
        $this->revocataIl = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}