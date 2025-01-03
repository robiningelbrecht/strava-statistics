<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

use App\Infrastructure\Exception\EntityNotFound;
use Doctrine\DBAL\Connection;

final readonly class DbalKeyValueStore implements KeyValueStore
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(KeyValue $keyValue): void
    {
        $sql = 'REPLACE INTO KeyValue (`key`, `value`)
        VALUES (:key, :value)';

        $this->connection->executeStatement($sql, [
            'key' => $keyValue->getKey()->value,
            'value' => $keyValue->getValue(),
        ]);
    }

    public function clear(Key $key): void
    {
        $sql = 'DELETE FROM KeyValue WHERE `key` = :key';

        $this->connection->executeStatement($sql, [
            'key' => $key->value,
        ]);
    }

    public function find(Key $key): Value
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('KeyValue')
            ->andWhere('`key` = :key')
            ->setParameter('key', $key->value);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('KeyValue "%s" not found', $key->value));
        }

        return Value::fromString($result['value']);
    }
}
