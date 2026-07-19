<?php

// base per i repository degli account affiliati, gestori circuiti e aziende noleggio
abstract class FAffiliato extends FRepository
{
    // nome della tabella del profilo societario, 1:1 con account
    abstract protected static function tabellaProfilo(): string;

    public static function loadByUtente(int $uid): ?array
    {
        return self::rowById($uid);
    }

    // elenco affiliati con anagrafica account, prima quelli in attesa
    public static function loadAllJoinUtente(): array
    {
        $tabella = static::tabellaProfilo();

        return FDataBase::executeQuery(
            "SELECT u.id, u.nome, u.cognome, u.email,
                    t.nome_societa, t.partita_iva, t.affiliazione
             FROM {$tabella} t
             JOIN account u ON u.id = t.id
             ORDER BY (t.affiliazione = 'in_attesa') DESC, u.cognome"
        )->fetchAllAssociative();
    }

    public static function updateAffiliazione(int $uid, string $stato): void
    {
        FPersistentManager::updateField(static::entityClass(), 'affiliazione', $stato, 'id', $uid);
    }

    public static function countPending(): int
    {
        return FPersistentManager::countWhere(
            static::entityClass(),
            'e.affiliazione = :stato',
            ['stato' => FAffiliazione::STATO_IN_ATTESA]
        );
    }

    public static function getAffiliazione(int $uid): string
    {
        return (string) (self::loadByUtente($uid)['affiliazione'] ?? '');
    }

    public static function isAffiliazioneApprovata(int $uid): bool
    {
        return self::getAffiliazione($uid) === FAffiliazione::STATO_APPROVATA;
    }
}
