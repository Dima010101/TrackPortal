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

    public function __construct(
        string $nome = '',
        string $cognome = '',
        string $email = '',
        string $password = '',
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataInizioSospensione = null,
        ?string $dataFineSospensione = null,
        ?string $dataCreazione = null,
        string $nomeSocieta = '',
        string $partitaIva = '',
        string $affiliazione = 'in_attesa'
    ) {
        parent::__construct(
            $id,
            $nome,
            $cognome,
            $email,
            $password,
            $statoAccount,
            $dataInizioSospensione,
            $dataFineSospensione,
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
}
