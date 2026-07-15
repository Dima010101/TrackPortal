<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * entità che gestisce le sessioni in pista
 */
#[ORM\Entity]
#[ORM\Table(name: 'sessione')]
class ESessione
{
    /** Categorie selezionabili dal gestore*/
    public const CATEGORIE = ['amatoriale', 'professionistica', 'privata'];

    /**
     * tabella che associa i tipi di sessione alle categorie di piloti
     */
    public static function categoriaPilotaAmmessa(string $statoSessione): ?string
    {
        return match ($statoSessione) {
            'amatoriale'       => 'amatoriale',
            'professionistica' => 'professionista',
            default            => null,
        };
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'circuito_id', type: 'integer')]
    protected int $circuitoId;

    #[ORM\Column(name: 'inizio', type: 'string', length: 19)]
    protected string $inizio;

    #[ORM\Column(name: 'fine', type: 'string', length: 19)]
    protected string $fine;

    #[ORM\Column(name: 'tariffa_accesso', type: 'decimal', precision: 10, scale: 2)]
    protected float $tariffaAccesso = 0.0;

    #[ORM\Column(name: 'posti_max', type: 'integer')]
    protected int $postiMax;

    #[ORM\Column(name: 'posti_per_box', type: 'integer')]
    protected int $postiPerBox;

    #[ORM\Column(name: 'note', type: 'text', nullable: true)]
    protected ?string $note = null;

    #[ORM\Column(name: 'stato', type: 'string', length: 20)]
    protected string $stato;

    #[ORM\Column(name: 'causa_annullamento', type: 'string', length: 255, nullable: true)]
    protected ?string $causaAnnullamento = null;

    #[ORM\Column(name: 'data_creazione', type: 'string', length: 19, nullable: true)]
    protected ?string $dataCreazione = null;

    public function __construct(
        int $circuitoId = 0,
        string $inizio = '',
        string $fine = '',
        float $tariffaAccesso = 0.0,
        int $postiMax = 1,
        int $postiPerBox = 1,
        ?string $note = null,
        string $stato = 'privata',
        ?int $id = null,
        ?string $dataCreazione = null,
        ?string $causaAnnullamento = null
    ) {
        $this->circuitoId     = $circuitoId;
        $this->inizio         = $inizio;
        $this->fine           = $fine;
        $this->tariffaAccesso = max(0.0, $tariffaAccesso);
        $this->postiMax       = max(1, $postiMax);
        $this->postiPerBox    = max(1, $postiPerBox);
        $this->note           = $note;
        $this->stato          = $stato;
        $this->id            = $id;
        $this->causaAnnullamento = $causaAnnullamento;
        $this->dataCreazione = $dataCreazione
            ?? (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    public function getId(): ?int              { return $this->id; }
    public function getCircuitoId(): int        { return $this->circuitoId; }
    public function getInizio(): string           { return $this->inizio; }
    public function getFine(): string           { return $this->fine; }
    public function getTariffaAccesso(): float  { return $this->tariffaAccesso; }
    public function setTariffaAccesso(float $v): void { $this->tariffaAccesso = max(0.0, $v); }
    public function getPostiMax(): int          { return $this->postiMax; }
    public function getPostiPerBox(): int       { return $this->postiPerBox; }
    public function getNote(): ?string          { return $this->note; }
    public function getStato(): string          { return $this->stato; }
    public function getCausaAnnullamento(): ?string { return $this->causaAnnullamento; }
    public function setCausaAnnullamento(?string $v): void { $this->causaAnnullamento = $v; }
    public function getDataCreazione(): ?string { return $this->dataCreazione; }

    public function setInizio(string $v): void  { $this->inizio = $v; }
    public function setFine(string $v): void    { $this->fine = $v; }
    public function setPostiMax(int $v): void   { $this->postiMax = max(1, $v); }
    public function setPostiPerBox(int $v): void { $this->postiPerBox = max(1, $v); }
    public function setNote(?string $v): void   { $this->note = $v; }
    public function setStato(string $v): void   { $this->stato = $v; }

    public function aggiornaDettagli(
        string $inizio,
        string $fine,
        float $tariffaAccesso,
        int $postiMax,
        int $postiPerBox,
        ?string $note,
        string $stato = 'privata'
    ): void {
        $this->inizio         = $inizio;
        $this->fine           = $fine;
        $this->tariffaAccesso = max(0.0, $tariffaAccesso);
        $this->postiMax       = max(1, $postiMax);
        $this->postiPerBox    = max(1, $postiPerBox);
        $this->note           = $note;
        $this->stato          = $stato;
    }


    /** verifica se la sessione è stata annullata dal gestore */
    public function isAnnullata(): bool
    {
        return $this->stato === 'annullata';
    }

    /**
     * Categoria di pilota ammessa a prenotare questa sessione (null se non prenotabile).
     */
    public function categoriaAmmessa(): ?string
    {
        return self::categoriaPilotaAmmessa($this->stato);
    }

    /** restituisce vero se un pilota della categoria indicata può prenotare questa sessione. */
    public function ammettePilota(string $categoriaPilota): bool
    {
        $ammessa = $this->categoriaAmmessa();

        return $ammessa !== null && $ammessa === $categoriaPilota;
    }

   /**
     * restituisce vero se la sessione è già conclusa rispetto all'istante indicato.
     * Da errore se data di fine è malformata
     */
    public function isScaduta(DateTimeImmutable $rispettoA): bool
    {
        return new DateTimeImmutable($this->fine) <= $rispettoA;
    }

    /** restituisce vero se tutti i posti sono occupati. */
    public function isAlCompleto(int $postiOccupati): bool
    {
        return $postiOccupati >= $this->postiMax;
    }
}

