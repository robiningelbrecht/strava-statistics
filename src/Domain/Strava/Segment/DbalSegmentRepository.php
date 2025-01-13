<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Name;

final readonly class DbalSegmentRepository extends DbalRepository implements SegmentRepository
{
    public function add(Segment $segment): void
    {
        $sql = 'INSERT INTO Segment (segmentId, name, data)
        VALUES (:segmentId, :name, :data)';

        $this->connection->executeStatement($sql, [
            'segmentId' => $segment->getId(),
            'name' => $segment->getName(),
            'data' => Json::encode($segment->getData()),
        ]);
    }

    public function find(SegmentId $segmentId): Segment
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Segment')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $segmentId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Segment "%s" not found', $segmentId));
        }

        return $this->hydrate($result);
    }

    public function findAll(): Segments
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*', '(SELECT COUNT(*) FROM SegmentEffort WHERE SegmentEffort.segmentId = Segment.segmentId) as countCompleted')
            ->from('Segment')
            ->orderBy('countCompleted', 'DESC');

        return Segments::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): Segment
    {
        return Segment::fromState(
            segmentId: SegmentId::fromString($result['segmentId']),
            name: Name::fromString($result['name']),
            data: Json::decode($result['data']),
        );
    }

    public function deleteOrphaned(): void
    {
        $this->connection->executeStatement('DELETE FROM Segment WHERE NOT EXISTS(
            SELECT 1 FROM SegmentEffort WHERE SegmentEffort.segmentId = Segment.segmentId
        )');
    }
}
