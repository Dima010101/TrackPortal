<?php

/**
 * repository di dominio: incapsula query specifiche per entità
 */
abstract class FRepository
{
    abstract protected static function entityClass(): string;

    public static function countAll(): int
    {
        return FPersistentManager::countAll(static::entityClass());
    }

    /**
     * persiste l'entità e ne restituisce l'id
     */
    protected static function persistAndId(object $entity): int
    {
        FPersistentManager::persist($entity);

        return (int) ($entity->getId() ?? 0);
    }

    /**
     * riga dell'entità identificata da id, o null se assente
     */
    protected static function rowById(int $id): ?array
    {
        $entity = FPersistentManager::find(static::entityClass(), $id);

        return $entity !== null ? FPersistentManager::entityToRow($entity) : null;
    }
}
