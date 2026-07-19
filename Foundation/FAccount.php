<?php

// repository account utente
class FAccount extends FRepository
{
    protected static function entityClass(): string
    {
        return EAccount::class;
    }

    // load singola entità, l'ORM ricompone il sottotipo concreto dal discriminante ruolo
    public static function loadEntityById(int $id): ?EAccount
    {
        $account = FPersistentManager::find(EAccount::class, $id);

        return $account instanceof EAccount ? $account : null;
    }

    // entità account da una riga, il load passa dall'ORM usando solo l'id
    public static function entityDaRiga(array $row): ?EAccount
    {
        $uid = (int) ($row['id'] ?? 0);

        return $uid > 0 ? self::loadEntityById($uid) : null;
    }

    public static function loadByEmail(string $email): ?array
    {
        $account = FPersistentManager::findOneBy(EAccount::class, ['email' => $email]);
        return $account instanceof EAccount ? FPersistentManager::entityToRow($account) : null;
    }

    public static function loadById(int $id): ?array
    {
        return self::rowById($id);
    }

    public static function rigaDopoVerificaPassword(string $email, string $password): ?array
    {
        $row = self::loadByEmail($email);
        if (!$row || !password_verify($password, (string)($row['password'] ?? ''))) {
            return null;
        }
        return $row;
    }

    public static function updateAnagrafica(int $id, string $nome, string $cognome, string $email): void
    {
        $account = FPersistentManager::find(EAccount::class, $id);
        if (!$account instanceof EAccount) {
            return;
        }
        $account->setNome($nome);
        $account->setCognome($cognome);
        $account->setEmail($email);
        FPersistentManager::flush();
    }

    public static function updatePasswordHash(int $id, string $hash): void
    {
        FPersistentManager::updateField(EAccount::class, 'password', $hash, 'id', $id);
    }

    // imposta o azzera il token monouso di conferma email
    public static function setTokenVerifica(int $id, ?string $token): void
    {
        $account = FPersistentManager::find(EAccount::class, $id);
        if (!$account instanceof EAccount) {
            return;
        }
        $account->setTokenVerifica($token);
        FPersistentManager::flush();
    }

    // marca l'email come confermata e consuma il token di verifica
    public static function markEmailVerificata(int $id): void
    {
        $account = FPersistentManager::find(EAccount::class, $id);
        if (!$account instanceof EAccount) {
            return;
        }
        $account->setEmailVerificata(true);
        $account->setTokenVerifica(null);
        FPersistentManager::flush();
    }

    public static function loadByTokenVerifica(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $account = FPersistentManager::findOneBy(EAccount::class, ['tokenVerifica' => $token]);

        return $account instanceof EAccount ? FPersistentManager::entityToRow($account) : null;
    }

    // email degli amministratori attivi, destinatari delle notifiche di approvazione
    public static function loadAdminEmails(): array
    {
        return FDataBase::executeQuery(
            "SELECT nome, cognome, email
             FROM account
             WHERE ruolo = 'admin' AND stato_account = 'attivo'
             ORDER BY id"
        )->fetchAllAssociative();
    }
}
