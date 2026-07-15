<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

/**
 * accesso SQL nativo sulla connessione condivisa con Doctrine ORM
 */
class FDataBase
{
    public static function getInstance(): Connection
    {
        return FPersistentManager::connection();
    }

    public static function executeQuery(string $sql, array $parameters = [], array $types = []): Result
    {
        return self::getInstance()->executeQuery($sql, self::normalizeNamedParams($parameters), $types);
    }

    public static function executeStatement(string $sql, array $parameters = [], array $types = []): int|string
    {
        return self::getInstance()->executeStatement($sql, self::normalizeNamedParams($parameters), $types);
    }

    private static function normalizeNamedParams(array $parameters): array
    {
        $out = [];
        foreach ($parameters as $k => $v) {
            if (is_string($k) && str_starts_with($k, ':')) {
                $out[substr($k, 1)] = $v;
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
