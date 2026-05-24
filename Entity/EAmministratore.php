<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * Sottoclasse di EAccount per gli amministratori.
 */
#[ORM\Entity]
#[ORM\Table(name: 'amministratore')]
class EAmministratore extends EAccount
{
    public static string $ruolo = 'admin';

    public function __construct(
        string $nome,
        string $cognome,
        string $email,
        string $password,
        string $statoAccount = 'attivo',
        ?int $id = null,
        ?string $dataInizioSospensione = null,
        ?string $dataFineSospensione = null,
        ?string $dataCreazione = null
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
    }
}
