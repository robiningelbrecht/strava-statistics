<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts\SegmentEffortsWereDeleted;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentEffortRepository extends DbalRepository implements SegmentEffortRepository
{
    public function __construct(
        Connection $connection,
        private EventBus $eventBus,
    ) {
        parent::__construct($connection);
    }

    public function add(SegmentEffort $segmentEffort): void
    {
        $sql = 'INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, name, elapsedTimeInSeconds, distance, averageWatts)
                VALUES (:segmentEffortId, :segmentId, :activityId, :startDateTime, :name, :elapsedTimeInSeconds, :distance, :averageWatts)';

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'segmentId' => $segmentEffort->getSegmentId(),
            'activityId' => $segmentEffort->getActivityId(),
            'startDateTime' => $segmentEffort->getStartDateTime(),
            'name' => $segmentEffort->getName(),
            'elapsedTimeInSeconds' => $segmentEffort->getElapsedTimeInSeconds(),
            'distance' => $segmentEffort->getDistance()->toMeter()->toInt(),
            'averageWatts' => $segmentEffort->getAverageWatts(),
        ]);
    }

    public function deleteForActivity(ActivityId $activityId): void
    {
        $sql = 'DELETE FROM SegmentEffort 
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql,
            [
                'activityId' => $activityId,
            ]
        );

        $this->eventBus->publishEvents([new SegmentEffortsWereDeleted()]);
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
            ->orderBy('elapsedTimeInSeconds', 'ASC');

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
            name: $result['name'],
            elapsedTimeInSeconds: $result['elapsedTimeInSeconds'],
            distance: Kilometer::from($result['distance'] / 1000),
            averageWatts: $result['averageWatts'],
        );
    }
}
