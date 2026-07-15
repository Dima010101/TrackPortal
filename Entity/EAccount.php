<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Superclasse per tutte le tipologie di account.
 */
#[ORM\Entity]
#[ORM\Table(name: 'account')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ruolo', type: 'string', length: 30)]
#[ORM\DiscriminatorMap([
    'pilota'             => EPilota::class,
    'gestore_circuiti'   => EGestoreCircuiti::class,
    'gestore_noleggio'   => EGestoreNoleggio::class,
    'admin'              => EAmministratore::class,
])]
abstract class EAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'nome', type: 'string', length: 80)]
    protected string $nome;

    #[ORM\Column(name: 'cognome', type: 'string', length: 80)]
    protected string $cognome;

    #[ORM\Column(name: 'email', type: 'string', length: 190, unique: true)]
    protected string $email;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    protected string $password;

    #[ORM\Column(name: 'stato_account', type: 'string', length: 20)]
    protected string $statoAccount;

    #[ORM\Column(name: 'data_creazione', type: 'string', length: 19, nullable: true)]
    protected ?string $dataCreazione = null;

    #[ORM\Column(name: 'email_verificata', type: 'boolean')]
    protected bool $emailVerificata = false;

    #[ORM\Column(name: 'token_verifica', type: 'string', length: 64, nullable: true)]
    protected ?string $tokenVerifica = null;

    protected function __construct(
        string $nome,
        string $cognome,
        string $email,
        string $password,
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataCreazione = null
    ) {
        $this->id            = $id;
        $this->nome          = $nome;
        $this->cognome       = $cognome;
        $this->email         = $email;
        $this->password      = $password;
        $this->statoAccount  = $statoAccount;
        $this->dataCreazione = $dataCreazione;
    }
    public function getId(): ?int                                 { return $this->id; }
    public function setId(?int $id): void                         { $this->id = $id; }
    public function getNome(): string                             { return $this->nome; }
    public function setNome(string $nome): void                   { $this->nome = $nome; }
    public function getCognome(): string                          { return $this->cognome; }
    public function setCognome(string $cognome): void             { $this->cognome = $cognome; }
    public function getEmail(): string                            { return $this->email; }
    public function setEmail(string $email): void                 { $this->email = $email; }
    public function getPassword(): string                         { return $this->password; }
    public function setPassword(string $password): void           { $this->password = $password; }
    public function getStatoAccount(): string                     { return $this->statoAccount; }
    public function setStatoAccount(string $statoAccount): void   { $this->statoAccount = $statoAccount; }
    public function getDataCreazione(): ?string                   { return $this->dataCreazione; }
    public function isEmailVerificata(): bool                     { return $this->emailVerificata; }
    public function setEmailVerificata(bool $v): void             { $this->emailVerificata = $v; }
    public function getTokenVerifica(): ?string                   { return $this->tokenVerifica; }
    public function setTokenVerifica(?string $token): void        { $this->tokenVerifica = $token; }
}
