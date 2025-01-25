<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;

final readonly class DbalActivitySplitRepository extends DbalRepository implements ActivitySplitRepository
{
    public function findBy(ActivityId $activityId, UnitSystem $unitSystem): ActivitySplits
    {
        $sql = 'SELECT * FROM ActivitySplit WHERE activityId = :activityId AND unitSystem = :unitSystem';
        $results = $this->connection->executeQuery($sql, [
            'activityId' => $activityId,
            'unitSystem' => $unitSystem->value,
        ])->fetchAllAssociative();

        return ActivitySplits::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $results
        ));
    }

    public function isImportedForActivity(ActivityId $activityId): bool
    {
        return $this->connection
                ->executeQuery('SELECT COUNT(*) FROM ActivitySplit WHERE activityId = :activityId', [
                    'activityId' => $activityId,
                ])
                ->fetchOne() > 0;
    }

    public function add(ActivitySplit $activitySplit): void
    {
        $sql = 'INSERT INTO ActivitySplit (
            activityId, unitSystem, splitNumber, distance, elapsedTimeInSeconds, movingTimeInSeconds,
            elevationDifference, averageSpeed, paceZone
        ) VALUES(
            :activityId, :unitSystem, :splitNumber, :distance, :elapsedTimeInSeconds, :movingTimeInSeconds,
            :elevationDifference, :averageSpeed, :paceZone
        )';

        $this->connection->executeStatement($sql, [
            'activityId' => $activitySplit->getActivityId(),
            'unitSystem' => $activitySplit->getUnitSystem()->value,
            'splitNumber' => $activitySplit->getSplitNumber(),
            'distance' => $activitySplit->getDistance()->toFloat(),
            'elapsedTimeInSeconds' => $activitySplit->getElapsedTimeInSeconds(),
            'movingTimeInSeconds' => $activitySplit->getMovingTimeInSeconds(),
            'elevationDifference' => $activitySplit->getElevationDifference()->toFloat(),
            'averageSpeed' => $activitySplit->getAverageSpeed()->toFloat(),
            'paceZone' => $activitySplit->getPaceZone(),
        ]);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ActivitySplit
    {
        return ActivitySplit::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            unitSystem: UnitSystem::from($result['unitSystem']),
            splitNumber: $result['splitNumber'],
            distance: Meter::from($result['distance']),
            elapsedTimeInSeconds: $result['elapsedTimeInSeconds'],
            movingTimeInSeconds: $result['movingTimeInSeconds'],
            elevationDifference: Meter::from($result['elevationDifference']),
            averageSpeed: MetersPerSecond::from($result['averageSpeed']),
            paceZone: $result['paceZone']
        );
    }
}
