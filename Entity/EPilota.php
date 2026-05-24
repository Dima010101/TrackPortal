<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Sottoclasse di EAccount per i piloti.
 */
#[ORM\Entity]
#[ORM\Table(name: 'pilota')]
class EPilota extends EAccount
{
    public static string $ruolo = 'pilota';

    #[ORM\Column(name: 'categoria', type: 'string', length: 20)]
    protected string $categoria;

    #[ORM\Column(name: 'licenza', type: 'string', length: 80, nullable: true)]
    protected ?string $licenza = null;

    #[ORM\Column(name: 'scadenza_licenza', type: 'string', length: 10, nullable: true)]
    protected ?string $scadenzaLicenza = null;

    public function __construct(
        string $nome,
        string $cognome,
        string $email,
        string $password,
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataInizioSospensione = null,
        ?string $dataFineSospensione = null,
        ?string $dataCreazione = null,
        string $categoria = 'amatoriale',
        ?string $licenza = null,
        ?string $scadenzaLicenza = null
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
        $this->categoria       = $categoria;
        $this->licenza         = $licenza;
        $this->scadenzaLicenza = $scadenzaLicenza;
    }
    public function getCategoria(): string                             { return $this->categoria; }
    public function setCategoria(string $categoria): void              { $this->categoria = $categoria; }
    public function getLicenza(): ?string                              { return $this->licenza; }
    public function setLicenza(?string $licenza): void                 { $this->licenza = $licenza; }
    public function getScadenzaLicenza(): ?string                      { return $this->scadenzaLicenza; }
    public function setScadenzaLicenza(?string $scadenzaLicenza): void { $this->scadenzaLicenza = $scadenzaLicenza; }
}
