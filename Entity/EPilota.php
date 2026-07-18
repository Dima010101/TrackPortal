<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Sottoclasse di EAccount per i piloti
 */
#[ORM\Entity]
#[ORM\Table(name: 'pilota')]
class EPilota extends EAccount
{
    public static string $ruolo = 'pilota';

    /** Stati di convalida dei documenti */
    public const DOC_IN_ATTESA = 'in_attesa';
    public const DOC_APPROVATI = 'approvati';
    public const DOC_RESPINTI  = 'respinti';

    #[ORM\Column(name: 'categoria', type: 'string', length: 20)]
    protected string $categoria;

    #[ORM\Column(name: 'licenza', type: 'string', length: 80, nullable: true)]
    protected ?string $licenza = null;

    #[ORM\Column(name: 'scadenza_licenza', type: 'string', length: 10, nullable: true)]
    protected ?string $scadenzaLicenza = null;

    #[ORM\Column(name: 'certificato_medico_path', type: 'string', length: 255, nullable: true)]
    protected ?string $certificatoMedicoPath = null;

    #[ORM\Column(name: 'licenza_path', type: 'string', length: 255, nullable: true)]
    protected ?string $licenzaPath = null;

    #[ORM\Column(name: 'documenti_stato', type: 'string', length: 20)]
    protected string $documentiStato = self::DOC_IN_ATTESA;

    #[ORM\Column(name: 'codice_fiscale', type: 'string', length: 16, nullable: true)]
    protected ?string $codiceFiscale = null;

    #[ORM\Column(name: 'indirizzo', type: 'string', length: 255)]
    protected string $indirizzo = '';

    #[ORM\Column(name: 'cap', type: 'string', length: 10)]
    protected string $cap = '';

    #[ORM\Column(name: 'comune', type: 'string', length: 120)]
    protected string $comune = '';

    #[ORM\Column(name: 'provincia', type: 'string', length: 4)]
    protected string $provincia = '';

    #[ORM\Column(name: 'fatt_indirizzo', type: 'string', length: 255)]
    protected string $fattIndirizzo = '';

    #[ORM\Column(name: 'fatt_cap', type: 'string', length: 10)]
    protected string $fattCap = '';

    #[ORM\Column(name: 'fatt_comune', type: 'string', length: 120)]
    protected string $fattComune = '';

    #[ORM\Column(name: 'fatt_provincia', type: 'string', length: 4)]
    protected string $fattProvincia = '';

    public function __construct(
        string $nome,
        string $cognome,
        string $email,
        string $password,
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataCreazione = null,
        string $categoria = 'amatoriale',
        ?string $licenza = null,
        ?string $scadenzaLicenza = null,
        ?string $certificatoMedicoPath = null,
        ?string $licenzaPath = null,
        string $documentiStato = self::DOC_IN_ATTESA
    ) {
        parent::__construct(
            $nome,
            $cognome,
            $email,
            $password,
            $statoAccount,
            $id,
            $dataCreazione
        );
        $this->categoria             = $categoria;
        $this->licenza               = $licenza;
        $this->scadenzaLicenza       = $scadenzaLicenza;
        $this->certificatoMedicoPath = $certificatoMedicoPath;
        $this->licenzaPath           = $licenzaPath;
        $this->documentiStato        = $documentiStato;
    }
    public function getCategoria(): string                             { return $this->categoria; }
    public function setCategoria(string $categoria): void              { $this->categoria = $categoria; }
    public function getLicenza(): ?string                              { return $this->licenza; }
    public function setLicenza(?string $licenza): void                 { $this->licenza = $licenza; }
    public function getScadenzaLicenza(): ?string                      { return $this->scadenzaLicenza; }
    public function setScadenzaLicenza(?string $scadenzaLicenza): void { $this->scadenzaLicenza = $scadenzaLicenza; }
    public function getCertificatoMedicoPath(): ?string               { return $this->certificatoMedicoPath; }
    public function setCertificatoMedicoPath(?string $v): void         { $this->certificatoMedicoPath = $v; }
    public function getLicenzaPath(): ?string                         { return $this->licenzaPath; }
    public function setLicenzaPath(?string $v): void                  { $this->licenzaPath = $v; }
    public function getDocumentiStato(): string                       { return $this->documentiStato; }
    public function setDocumentiStato(string $stato): void            { $this->documentiStato = $stato; }
    public function documentiApprovati(): bool                        { return $this->documentiStato === self::DOC_APPROVATI; }
    public function getCodiceFiscale(): ?string                       { return $this->codiceFiscale; }
    public function setCodiceFiscale(?string $v): void                { $this->codiceFiscale = $v; }
    public function getIndirizzo(): string                            { return $this->indirizzo; }
    public function setIndirizzo(string $v): void                     { $this->indirizzo = $v; }
    public function getCap(): string                                  { return $this->cap; }
    public function setCap(string $v): void                           { $this->cap = $v; }
    public function getComune(): string                               { return $this->comune; }
    public function setComune(string $v): void                        { $this->comune = $v; }
    public function getProvincia(): string                            { return $this->provincia; }
    public function setProvincia(string $v): void                     { $this->provincia = $v; }
    public function getFattIndirizzo(): string                        { return $this->fattIndirizzo; }
    public function setFattIndirizzo(string $v): void                 { $this->fattIndirizzo = $v; }
    public function getFattCap(): string                              { return $this->fattCap; }
    public function setFattCap(string $v): void                       { $this->fattCap = $v; }
    public function getFattComune(): string                           { return $this->fattComune; }
    public function setFattComune(string $v): void                    { $this->fattComune = $v; }
    public function getFattProvincia(): string                        { return $this->fattProvincia; }
    public function setFattProvincia(string $v): void                 { $this->fattProvincia = $v; }

    /** blocca la prenotazione senza entrambi i documenti caricati e approvati: messaggio o null */
    public function erroreDocumenti(): ?string
    {
        $cert = trim((string) $this->certificatoMedicoPath);
        $lic  = trim((string) $this->licenzaPath);
        if ($cert === '' || $lic === '') {
            return 'Per prenotare devi prima caricare entrambi i documenti '
                . '(certificato medico e patente/licenza) dal tuo account.';
        }

        if ($this->documentiStato === self::DOC_APPROVATI) {
            return null;
        }
        if ($this->documentiStato === self::DOC_RESPINTI) {
            return 'I tuoi documenti sono stati respinti dall\'amministrazione. '
                . 'Caricane di nuovi dal tuo account per poter prenotare le sessioni.';
        }

        return 'I tuoi documenti sono in attesa di convalida da parte dell\'amministrazione: '
            . 'non puoi ancora prenotare le sessioni.';
    }

    /** blocca la prenotazione con licenza scaduta: messaggio o null se valida */
    public function erroreLicenza(): ?string
    {
        $scadenza = trim((string) ($this->scadenzaLicenza ?? ''));
        if ($scadenza === '') {
            return null;
        }

        $dataScadenza = DateTimeImmutable::createFromFormat('!Y-m-d', $scadenza);
        if ($dataScadenza === false) {
            return null;
        }

        if ($dataScadenza < new DateTimeImmutable('today')) {
            return 'La tua licenza è scaduta il ' . $dataScadenza->format('d/m/Y')
                . '. Aggiorna la data di scadenza dal tuo account per poter prenotare le sessioni.';
        }

        return null;
    }
}
