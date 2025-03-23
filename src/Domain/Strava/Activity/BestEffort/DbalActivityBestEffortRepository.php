<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class DbalActivityBestEffortRepository extends DbalRepository implements ActivityBestEffortRepository
{
    public function add(ActivityBestEffort $activityBestEffort): void
    {
        $sql = 'INSERT INTO ActivityBestEffort (activityId, sportType, distanceInMeter, timeInSeconds)
        VALUES (:activityId, :sportType, :distanceInMeter, :timeInSeconds)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activityBestEffort->getActivityId(),
            'sportType' => $activityBestEffort->getSportType()->value,
            'distanceInMeter' => $activityBestEffort->getDistanceInMeter()->toInt(),
            'timeInSeconds' => $activityBestEffort->getTimeInSeconds(),
        ]);
    }

    public function findBestEffortsFor(SportType $sportType): ActivityBestEfforts
    {
        $sql = 'WITH BestEfforts AS (
                    SELECT distanceInMeter, MIN(timeInSeconds) AS bestTime
                    FROM ActivityBestEffort
                    WHERE sportType = :sportType
                    GROUP BY distanceInMeter
                )
                SELECT a.activityId, a.sportType, a.distanceInMeter, a.timeInSeconds
                FROM ActivityBestEffort a
                INNER JOIN BestEfforts b ON a.distanceInMeter = b.distanceInMeter AND a.timeInSeconds = b.bestTime
                WHERE a.sportType = :sportType
                ORDER BY a.distanceInMeter';

        return ActivityBestEfforts::fromArray(array_map(
            fn (array $result) => ActivityBestEffort::fromState(
                activityId: ActivityId::fromString($result['activityId']),
                distanceInMeter: Meter::from($result['distanceInMeter']),
                sportType: SportType::from($result['sportType']),
                timeInSeconds: $result['timeInSeconds']
            ),
            $this->connection->executeQuery($sql, ['sportType' => $sportType->value])->fetchAllAssociative()
        ));
    }

    public function findActivityIdsWithoutBestEfforts(): ActivityIds
    {
        $sql = 'SELECT activityId FROM Activity 
                  WHERE activityId NOT IN (SELECT activityId FROM ActivityBestEffort)';

        return ActivityIds::fromArray(array_map(
            fn (string $activityId) => ActivityId::fromString($activityId),
            $this->connection->executeQuery($sql)->fetchFirstColumn()
        ));
    }
}
