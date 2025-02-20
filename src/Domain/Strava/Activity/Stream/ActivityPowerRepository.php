<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;

interface ActivityPowerRepository
{
    public const array TIME_INTERVAL_IN_SECONDS = [5, 10, 30, 60, 300, 480, 1200, 3600];
    public const array TIME_INTERVAL_IN_SECONDS_OVERALL = [1, 5, 10, 15, 30, 45, 60, 120, 180, 240, 300, 390, 480, 720, 960, 1200, 1800, 2400, 3000, 3600];

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(ActivityId $activityId): array;

    /**
     * @return PowerOutput[]
     */
    public function findBest(ActivityType $activityType): array;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(ActivityId $activityId): array;
}
