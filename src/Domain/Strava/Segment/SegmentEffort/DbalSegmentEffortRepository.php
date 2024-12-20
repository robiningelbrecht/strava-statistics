<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentEffortRepository implements SegmentEffortRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function add(SegmentEffort $segmentEffort): void
    {
        $sql = 'INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, data)
        VALUES (:segmentEffortId, :segmentId, :activityId, :startDateTime, :data)';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'segmentId' => $segmentEffort->getSegmentId(),
            'activityId' => $segmentEffort->getActivityId(),
            'startDateTime' => $segmentEffort->getStartDateTime(),
            'data' => Json::encode($data),
        ]);
    }

    public function update(SegmentEffort $segmentEffort): void
    {
        $sql = 'UPDATE SegmentEffort 
        SET data = :data
        WHERE segmentEffortId = :segmentEffortId';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'data' => Json::encode($data),
        ]);
    }

    public function delete(SegmentEffort $segmentEffort): void
    {
        $sql = 'DELETE FROM SegmentEffort 
        WHERE segmentEffortId = :segmentEffortId';

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
        ]);
    }

    public function find(SegmentEffortId $segmentEffortId): SegmentEffort
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('segmentEffortId = :segmentEffortId')
            ->setParameter('segmentEffortId', $segmentEffortId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('segmentEffort "%s" not found', $segmentEffortId));
        }

        return $this->hydrate($result);
    }

    public function findBySegmentId(SegmentId $segmentId, ?int $limit = null): SegmentEfforts
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $segmentId)
            ->orderBy("JSON_EXTRACT(data, '$.elapsed_time')", 'ASC');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return SegmentEfforts::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function countBySegmentId(SegmentId $segmentId): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('COUNT(*)')
            ->from('SegmentEffort')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $segmentId);

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }

    public function findByActivityId(ActivityId $activityId): SegmentEfforts
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return SegmentEfforts::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): SegmentEffort
    {
        return SegmentEffort::fromState(
            segmentEffortId: SegmentEffortId::fromString($result['segmentEffortId']),
            segmentId: SegmentId::fromString($result['segmentId']),
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
        );
    }
}
