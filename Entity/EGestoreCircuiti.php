<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Sottoclasse di EAccount per i gestori di circuiti.
 */
#[ORM\Entity]
#[ORM\Table(name: 'gestore_circuiti')]
class EGestoreCircuiti extends EAccount
{
    public static string $ruolo = 'gestore_circuiti';

    #[ORM\Column(name: 'nome_societa', type: 'string', length: 150)]
    protected string $nomeSocieta;

    #[ORM\Column(name: 'partita_iva', type: 'string', length: 20, unique: true)]
    protected string $partitaIva;

    #[ORM\Column(name: 'affiliazione', type: 'string', length: 20)]
    protected string $affiliazione;

    #[ORM\Column(name: 'codice_fiscale', type: 'string', length: 20, nullable: true)]
    protected ?string $codiceFiscale = null;

    #[ORM\Column(name: 'indirizzo', type: 'string', length: 255)]
    protected string $indirizzo = '';

    #[ORM\Column(name: 'cap', type: 'string', length: 10)]
    protected string $cap = '';

    #[ORM\Column(name: 'comune', type: 'string', length: 120)]
    protected string $comune = '';

    #[ORM\Column(name: 'provincia', type: 'string', length: 4)]
    protected string $provincia = '';

    public function __construct(
        string $nome = '',
        string $cognome = '',
        string $email = '',
        string $password = '',
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataCreazione = null,
        string $nomeSocieta = '',
        string $partitaIva = '',
        string $affiliazione = 'in_attesa'
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
        $this->nomeSocieta  = $nomeSocieta;
        $this->partitaIva   = $partitaIva;
        $this->affiliazione = $affiliazione;
    }
    public function getNomeSocieta(): string                    { return $this->nomeSocieta; }
    public function setNomeSocieta(string $nomeSocieta): void   { $this->nomeSocieta = $nomeSocieta; }
    public function getPartitaIva(): string                     { return $this->partitaIva; }
    public function setPartitaIva(string $partitaIva): void     { $this->partitaIva = $partitaIva; }
    public function getAffiliazione(): string                   { return $this->affiliazione; }
    public function setAffiliazione(string $affiliazione): void { $this->affiliazione = $affiliazione; }
    public function getCodiceFiscale(): ?string             { return $this->codiceFiscale; }
    public function setCodiceFiscale(?string $v): void      { $this->codiceFiscale = $v; }
    public function getIndirizzo(): string                  { return $this->indirizzo; }
    public function setIndirizzo(string $v): void           { $this->indirizzo = $v; }
    public function getCap(): string                        { return $this->cap; }
    public function setCap(string $v): void                 { $this->cap = $v; }
    public function getComune(): string                     { return $this->comune; }
    public function setComune(string $v): void              { $this->comune = $v; }
    public function getProvincia(): string                  { return $this->provincia; }
    public function setProvincia(string $v): void           { $this->provincia = $v; }
}
