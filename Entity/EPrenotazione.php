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

    #[ORM\Column(name: 'inizio_sessione', type: 'string', length: 19)]
    protected string $inizioSessione;

    #[ORM\Column(name: 'fine_sessione', type: 'string', length: 19)]
    protected string $fineSessione;

    #[ORM\Column(name: 'numero_box', type: 'integer', nullable: true)]
    protected ?int $numeroBox = null;

    #[ORM\Column(name: 'assicurazione', type: 'boolean')]
    protected bool $assicurazione;

    #[ORM\Column(name: 'prezzo_importo', type: 'decimal', precision: 10, scale: 2)]
    protected float $prezzoImporto;

    #[ORM\Column(name: 'prezzo_valuta', type: 'string', length: 3)]
    protected string $prezzoValuta;

    #[ORM\Column(name: 'stato', type: 'string', length: 20)]
    protected string $stato;

    #[ORM\Column(name: 'carta_credito_id', type: 'integer', nullable: true)]
    protected ?int $cartaCreditoId = null;

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

    #[ORM\Column(name: 'imponibile_accesso', type: 'decimal', precision: 10, scale: 2)]
    protected float $imponibileAccesso = 0.0;

    #[ORM\Column(name: 'imponibile_noleggio', type: 'decimal', precision: 10, scale: 2)]
    protected float $imponibileNoleggio = 0.0;

    #[ORM\Column(name: 'imponibile_assicurazione', type: 'decimal', precision: 10, scale: 2)]
    protected float $imponibileAssicurazione = 0.0;

    public function __construct(
        int $pilotaId = 0,
        int $circuitoId = 0,
        string $inizioSessione = '',
        string $fineSessione = '',
        ?int $numeroBox = null,
        ?int $veicoloNoleggioId = null,
        ?string $targaVeicolo = null,
        bool $assicurazione = false,
        float $prezzoImporto = 0.0,
        string $prezzoValuta = 'EUR',
        string $stato = 'confermata',
        string $codiceIdentificativo = '',
        ?int $cartaCreditoId = null,
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
        $this->inizioSessione   = $inizioSessione;
        $this->fineSessione     = $fineSessione;
        $this->numeroBox             = $numeroBox;
        $this->assicurazione         = $assicurazione;
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
        $e = new self(
            (int)($r['pilota_id'] ?? 0),
            (int)($r['circuito_id'] ?? 0),
            (string)($r['inizio_sessione'] ?? ''),
            (string)($r['fine_sessione'] ?? ''),
            isset($r['numero_box']) && $r['numero_box'] !== null ? (int)$r['numero_box'] : null,
            isset($r['veicolo_noleggio_id']) && $r['veicolo_noleggio_id'] !== null
                ? (int)$r['veicolo_noleggio_id'] : null,
            $r['targa_veicolo'] ?? null,
            (bool)($r['assicurazione'] ?? false),
            (float)($r['prezzo_importo'] ?? 0.0),
            (string)($r['prezzo_valuta'] ?? 'EUR'),
            (string)($r['stato'] ?? 'confermata'),
            (string)($r['codice_identificativo'] ?? ''),
            isset($r['carta_credito_id']) && $r['carta_credito_id'] !== null ? (int)$r['carta_credito_id'] : null,
            isset($r['promozione_id']) && $r['promozione_id'] !== null ? (int)$r['promozione_id'] : null,
            (float)($r['sconto_importo'] ?? 0.0),
            (float)($r['rimborso_previsto'] ?? 0.0),
            $r['causa_cancellazione'] ?? null,
            isset($r['id']) ? (int)$r['id'] : null,
            $r['data_inserimento'] ?? null
        );

        $e->imponibileAccesso       = (float)($r['imponibile_accesso'] ?? 0.0);
        $e->imponibileNoleggio      = (float)($r['imponibile_noleggio'] ?? 0.0);
        $e->imponibileAssicurazione = (float)($r['imponibile_assicurazione'] ?? 0.0);

        return $e;
    }

    public function getId(): ?int                 { return $this->id; }
    public function getCodiceIdentificativo(): string { return $this->codiceIdentificativo; }
    public function getPilotaId(): int             { return $this->pilotaId; }
    public function getCircuitoId(): int         { return $this->circuitoId; }
    public function getVeicoloNoleggioId(): ?int { return $this->veicoloNoleggioId; }
    public function getTargaVeicolo(): ?string   { return $this->targaVeicolo; }
    public function setTargaVeicolo(?string $v): void { $this->targaVeicolo = $v; }
    public function getInizioSessione(): string { return $this->inizioSessione; }
    public function getFineSessione(): string   { return $this->fineSessione; }
    public function getNumeroBox(): ?int             { return $this->numeroBox; }
    public function setNumeroBox(?int $v): void      { $this->numeroBox = $v; }
    public function isAssicurazione(): bool      { return $this->assicurazione; }
    public function setAssicurazione(bool $v): void { $this->assicurazione = $v; }
    public function getPrezzoImporto(): float    { return $this->prezzoImporto; }
    public function setPrezzoImporto(float $v): void { $this->prezzoImporto = $v; }
    public function getPrezzoValuta(): string     { return $this->prezzoValuta; }
    public function getStato(): string           { return $this->stato; }
    public function setStato(string $v): void   { $this->stato = $v; }
    public function getCartaCreditoId(): ?int    { return $this->cartaCreditoId; }
    public function getPromozioneId(): ?int      { return $this->promozioneId; }
    public function getScontoImporto(): float    { return $this->scontoImporto; }
    public function getRimborsoPrevisto(): float { return $this->rimborsoPrevisto; }
    public function setRimborsoPrevisto(float $v): void { $this->rimborsoPrevisto = $v; }
    public function getCausaCancellazione(): ?string { return $this->causaCancellazione; }
    public function setCausaCancellazione(?string $v): void { $this->causaCancellazione = $v; }
    public function getDataInserimento(): ?string { return $this->dataInserimento; }
    public function getImponibileAccesso(): float       { return $this->imponibileAccesso; }
    public function setImponibileAccesso(float $v): void { $this->imponibileAccesso = $v; }
    public function getImponibileNoleggio(): float       { return $this->imponibileNoleggio; }
    public function setImponibileNoleggio(float $v): void { $this->imponibileNoleggio = $v; }
    public function getImponibileAssicurazione(): float       { return $this->imponibileAssicurazione; }
    public function setImponibileAssicurazione(float $v): void { $this->imponibileAssicurazione = $v; }

    // --- Pricing e codice prenotazione ---

    /** Codice univoco per una nuova prenotazione. */
    public static function generaCodice(): string
    {
        return 'TP-' . strtoupper(bin2hex(random_bytes(6)));
    }

    /**
     * Prezzo totale: base meno sconto (mai sotto zero), più assicurazione se
     * scelta. Unica regola di pricing, usata sia nel riepilogo che al salvataggio.
     */
    public static function calcolaPrezzoTotale(
        float $prezzoBase,
        float $sconto,
        bool $assicurazione,
        float $prezzoAssicurazione
    ): float {
        $totale = max(0.0, $prezzoBase - $sconto);
        if ($assicurazione) {
            $totale += $prezzoAssicurazione;
        }

        return $totale;
    }

    /**
     * Scompone il prezzo negli imponibili per la fatturazione. Lo sconto è
     * ripartito pro quota tra accesso e noleggio, così la somma torna sempre
     * col prezzo totale.
     */
    public function scomponiImponibili(
        float $prezzoBase,
        float $scontoPromozione,
        float $importoNoleggio,
        float $prezzoAssicurazione
    ): void {
        $baseAfter = round(max(0.0, $prezzoBase - $scontoPromozione), 2);
        if ($prezzoBase > 0) {
            $scontoNoleggio = round($scontoPromozione * $importoNoleggio / $prezzoBase, 2);
            $impNoleggio    = round(max(0.0, $importoNoleggio - $scontoNoleggio), 2);
            $impAccesso     = round(max(0.0, $baseAfter - $impNoleggio), 2);
        } else {
            $impNoleggio = 0.0;
            $impAccesso  = $baseAfter;
        }

        $this->imponibileAccesso       = $impAccesso;
        $this->imponibileNoleggio      = $impNoleggio;
        $this->imponibileAssicurazione = $this->assicurazione ? round($prezzoAssicurazione, 2) : 0.0;
    }

    // --- Rimborso alla cancellazione ---

    /**
     * Percentuali di rimborso (0-100): con assicurazione sempre 100%, altrimenti
     * a scaglioni in base all'anticipo sull'inizio della sessione.
     *
     * NB: gli stessi valori sono citati in partials/box_assicurazione.tpl e nei
     * template di cancellazione: se cambiano qui, aggiornare anche lì.
     */
    public const RIMBORSO_PERC_ASSICURATO       = 100; // assicurato: sempre 100%
    public const RIMBORSO_PERC_ANTICIPO_ALTO    = 90;  // >= 7 giorni prima
    public const RIMBORSO_PERC_ANTICIPO_MEDIO   = 70;  // 3-7 giorni prima
    public const RIMBORSO_PERC_ANTICIPO_BASSO   = 50;  // 1-3 giorni prima
    public const RIMBORSO_PERC_ANTICIPO_MINIMO  = 30;  // < 24 ore prima

    /** Percentuale di rimborso per una cancellazione ($ora: default adesso). */
    public static function percentualeRimborso(
        bool $assicurato,
        ?string $inizioSessione = null,
        ?DateTimeImmutable $ora = null
    ): int {
        if ($assicurato) {
            return self::RIMBORSO_PERC_ASSICURATO;
        }

        // Senza data non si calcola l'anticipo: fascia intermedia.
        if ($inizioSessione === null || $inizioSessione === '') {
            return self::RIMBORSO_PERC_ANTICIPO_MEDIO;
        }

        $ora ??= new DateTimeImmutable('now');
        try {
            $inizio = new DateTimeImmutable($inizioSessione);
        } catch (Exception) {
            return self::RIMBORSO_PERC_ANTICIPO_MEDIO;
        }

        $giorniAnticipo = ($inizio->getTimestamp() - $ora->getTimestamp()) / 86400;
        if ($giorniAnticipo >= 7) {
            return self::RIMBORSO_PERC_ANTICIPO_ALTO;
        }
        if ($giorniAnticipo >= 3) {
            return self::RIMBORSO_PERC_ANTICIPO_MEDIO;
        }
        if ($giorniAnticipo >= 1) {
            return self::RIMBORSO_PERC_ANTICIPO_BASSO;
        }

        return self::RIMBORSO_PERC_ANTICIPO_MINIMO;
    }

    public static function calcolaRimborsoCancellazione(
        float $importoPagato,
        bool $assicurato = false,
        ?string $inizioSessione = null,
        ?DateTimeImmutable $ora = null
    ): float {
        $perc = self::percentualeRimborso($assicurato, $inizioSessione, $ora);

        return round(max(0.0, $importoPagato) * $perc / 100, 2);
    }
}
