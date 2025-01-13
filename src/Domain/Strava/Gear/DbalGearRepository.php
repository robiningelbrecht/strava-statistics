<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DbalGearRepository extends DbalRepository implements GearRepository
{
    public function add(Gear $gear): void
    {
        $sql = 'INSERT INTO Gear (gearId, createdOn, data, distanceInMeter)
        VALUES (:gearId, :createdOn, :data, :distanceInMeter)';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'data' => Json::encode($gear->getData()),
            'distanceInMeter' => $gear->getDistance()->toMeter(),
        ]);
    }

    public function update(Gear $gear): void
    {
        $sql = 'UPDATE Gear 
        SET distanceInMeter = :distanceInMeter,
        data = :data
        WHERE gearId = :gearId';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'distanceInMeter' => $gear->getDistance()->toMeter(),
            'data' => Json::encode($gear->getData()),
        ]);
    }

    public function findAll(): Gears
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->orderBy('distanceInMeter', 'DESC');

        return Gears::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function find(GearId $gearId): Gear
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->andWhere('gearId = :gearId')
            ->setParameter('gearId', $gearId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $gearId));
        }

        return $this->hydrate($result);
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): Gear
    {
        return Gear::fromState(
            gearId: GearId::fromString($result['gearId']),
            data: Json::decode($result['data']),
            distanceInMeter: $result['distanceInMeter'],
            createdOn: SerializableDateTime::fromString($result['createdOn']),
        );
    }
}
