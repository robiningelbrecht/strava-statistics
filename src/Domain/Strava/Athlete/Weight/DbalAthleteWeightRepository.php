<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalAthleteWeightRepository implements AthleteWeightRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function removeAll(): void
    {
        $this->connection->executeStatement('DELETE FROM AthleteWeight');
    }

    public function save(AthleteWeight $weight): void
    {
        $sql = 'REPLACE INTO AthleteWeight (`on`, weightInGrams)
        VALUES (:on, :weightInGrams)';

        $this->connection->executeStatement($sql, [
            'on' => $weight->getOn(),
            'weightInGrams' => $weight->getWeightInGrams(),
        ]);
    }

    public function find(SerializableDateTime $on): AthleteWeight
    {
        $dateTime = SerializableDateTime::fromString($on->format('Y-m-d'));
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('AthleteWeight')
            ->andWhere('`on` <= :date')
            ->setParameter('date', $dateTime)
            ->setMaxResults(1)
            ->orderBy('`on`', 'DESC');

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('AthleteWeight for date "%s" not found', $dateTime));
        }

        return $this->hydrate($result);
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): AthleteWeight
    {
        return AthleteWeight::fromState(
            on: SerializableDateTime::fromString($result['on']),
            weightInGrams: (int) $result['weightInGrams']
        );
    }
}