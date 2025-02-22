<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;

interface ActivityPowerRepository
{
    public const array TIME_INTERVAL_IN_SECONDS = [5, 10, 30, 60, 300, 480, 1200, 3600];
    public const array TIME_INTERVAL_IN_SECONDS_OVERALL = [1, 5, 10, 15, 30, 45, 60, 120, 180, 240, 300, 390, 480, 600, 720, 900, 960, 1200, 1800, 2400, 3000, 3600];
    public const array EFTP_FACTORS = [
        300 => 0.79,
        600 => 0.86,
        900 => 0.92,
        1200 => 0.95,
        1800 => 0.96,
        2400 => 0.97,
        3000 => 0.99,
        3600 => 1
    ];

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(ActivityId $activityId): array;

    /**
     * @return PowerOutput[]
     */
    public function findBest(): array;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(ActivityId $activityId): array;

    /**
     * @return ?PowerOutput
     */
    public function calculateEFTP(Activity $activity): ?PowerOutput;
}
