<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * EPromozione — offerta proposta da un'azienda di noleggio o da un gestore di circuiti.
 * La promozione viene applicata automaticamente ai destinatari.
 */
#[ORM\Entity]
#[ORM\Table(name: 'promozione')]
class EPromozione
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'azienda_noleggio_id', type: 'integer', nullable: true)]
    protected ?int $aziendaNoleggioId = null;

    #[ORM\Column(name: 'gestore_circuiti_id', type: 'integer', nullable: true)]
    protected ?int $gestoreCircuitiId = null;

    #[ORM\Column(name: 'veicolo_noleggio_id', type: 'integer', nullable: true)]
    protected ?int $veicoloNoleggioId = null;

    #[ORM\Column(name: 'circuito_id', type: 'integer', nullable: true)]
    protected ?int $circuitoId = null;

    #[ORM\Column(name: 'titolo', type: 'string', length: 150)]
    protected string $titolo;

    #[ORM\Column(name: 'descrizione', type: 'text', nullable: true)]
    protected ?string $descrizione = null;

    #[ORM\Column(name: 'tipo_sconto', type: 'string', length: 20)]
    protected string $tipoSconto;

    #[ORM\Column(name: 'valore', type: 'decimal', precision: 10, scale: 2)]
    protected float $valore;

    #[ORM\Column(name: 'codice', type: 'string', length: 40, unique: true)]
    protected string $codice;

    #[ORM\Column(name: 'data_inizio', type: 'string', length: 10, nullable: true)]
    protected ?string $dataInizio = null;

    #[ORM\Column(name: 'data_fine', type: 'string', length: 10, nullable: true)]
    protected ?string $dataFine = null;

    #[ORM\Column(name: 'segmento_destinatari', type: 'string', length: 40)]
    protected string $segmentoDestinatari;

    #[ORM\Column(name: 'soglia_prenotazioni', type: 'integer', nullable: true)]
    protected ?int $sogliaPrenotazioni = null;

    #[ORM\Column(name: 'data_prenotazione_inizio', type: 'string', length: 10, nullable: true)]
    protected ?string $dataPrenotazioneInizio = null;

    #[ORM\Column(name: 'data_prenotazione_fine', type: 'string', length: 10, nullable: true)]
    protected ?string $dataPrenotazioneFine = null;

    #[ORM\Column(name: 'frequenza_utilizzo', type: 'integer')]
    protected int $frequenzaUtilizzo;

    #[ORM\Column(name: 'stato_promozione', type: 'string', length: 20)]
    protected string $statoPromozione;

    public function __construct(
        ?int $aziendaNoleggioId = null, ?int $veicoloNoleggioId = null, ?int $circuitoId = null, string $titolo = '',
        ?string $descrizione = null, string $tipoSconto = 'percentuale',
        float $valore = 0.0, string $codice = '', ?string $dataInizio = null,
        ?string $dataFine = null, int $frequenzaUtilizzo = 0,
        string $statoPromozione = 'attiva', ?int $id = null,
        ?int $gestoreCircuitiId = null, string $segmentoDestinatari = 'tutti',
        ?int $sogliaPrenotazioni = null, ?string $dataPrenotazioneInizio = null,
        ?string $dataPrenotazioneFine = null
    ) {
        $this->id                 = $id;
        $this->aziendaNoleggioId  = $aziendaNoleggioId;
        $this->gestoreCircuitiId  = $gestoreCircuitiId;
        $this->veicoloNoleggioId  = $veicoloNoleggioId;
        $this->circuitoId         = $circuitoId;
        $this->titolo             = $titolo;
        $this->descrizione        = $descrizione;
        $this->tipoSconto         = $tipoSconto;
        $this->valore             = $valore;
        $this->codice             = $codice;
        $this->dataInizio         = $dataInizio !== '' ? $dataInizio : null;
        $this->dataFine           = $dataFine !== '' ? $dataFine : null;
        $this->segmentoDestinatari = $segmentoDestinatari;
        $this->sogliaPrenotazioni = $sogliaPrenotazioni;
        $this->dataPrenotazioneInizio = $dataPrenotazioneInizio;
        $this->dataPrenotazioneFine = $dataPrenotazioneFine;
        $this->frequenzaUtilizzo  = $frequenzaUtilizzo;
        $this->statoPromozione    = $statoPromozione;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            isset($r['azienda_noleggio_id']) && $r['azienda_noleggio_id'] !== null
                ? (int)$r['azienda_noleggio_id'] : null,
            isset($r['veicolo_noleggio_id']) && $r['veicolo_noleggio_id'] !== null
                ? (int)$r['veicolo_noleggio_id'] : null,
            isset($r['circuito_id']) && $r['circuito_id'] !== null ? (int)$r['circuito_id'] : null,
            (string)($r['titolo'] ?? ''),
            $r['descrizione'] ?? null,
            (string)($r['tipo_sconto'] ?? 'percentuale'),
            (float)($r['valore'] ?? 0.0),
            (string)($r['codice'] ?? ''),
            isset($r['data_inizio']) && $r['data_inizio'] !== '' ? (string)$r['data_inizio'] : null,
            isset($r['data_fine']) && $r['data_fine'] !== '' ? (string)$r['data_fine'] : null,
            (int)($r['frequenza_utilizzo'] ?? 0),
            (string)($r['stato_promozione'] ?? 'attiva'),
            isset($r['id']) ? (int)$r['id'] : null,
            isset($r['gestore_circuiti_id']) && $r['gestore_circuiti_id'] !== null
                ? (int)$r['gestore_circuiti_id'] : null,
            (string)($r['segmento_destinatari'] ?? 'tutti'),
            isset($r['soglia_prenotazioni']) && $r['soglia_prenotazioni'] !== null
                ? (int)$r['soglia_prenotazioni'] : null,
            $r['data_prenotazione_inizio'] ?? null,
            $r['data_prenotazione_fine'] ?? null
        );
    }

    public function getId(): ?int                 { return $this->id; }
    public function getAziendaNoleggioId(): ?int { return $this->aziendaNoleggioId; }
    public function getGestoreCircuitiId(): ?int { return $this->gestoreCircuitiId; }
    public function getVeicoloNoleggioId(): ?int { return $this->veicoloNoleggioId; }
    public function getCircuitoId(): ?int       { return $this->circuitoId; }
    public function getTitolo(): string          { return $this->titolo; }
    public function getDescrizione(): ?string    { return $this->descrizione; }
    public function getTipoSconto(): string      { return $this->tipoSconto; }
    public function getValore(): float           { return $this->valore; }
    public function getCodice(): string          { return $this->codice; }
    public function getDataInizio(): ?string      { return $this->dataInizio; }
    public function getDataFine(): ?string        { return $this->dataFine; }
    public function getSegmentoDestinatari(): string { return $this->segmentoDestinatari; }
    public function getSogliaPrenotazioni(): ?int { return $this->sogliaPrenotazioni; }
    public function getDataPrenotazioneInizio(): ?string { return $this->dataPrenotazioneInizio; }
    public function getDataPrenotazioneFine(): ?string { return $this->dataPrenotazioneFine; }
    public function getFrequenzaUtilizzo(): int  { return $this->frequenzaUtilizzo; }
    public function getStatoPromozione(): string { return $this->statoPromozione; }
}
