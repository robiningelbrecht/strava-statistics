<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\SportType\SportType;

interface ActivityBestEffortRepository
{
    public function add(ActivityBestEffort $activityBestEffort): void;

    public function findBestEffortsFor(SportType $sportType): ActivityBestEfforts;

    public function findActivityIdsThatNeedBestEffortsCalculation(): ActivityIds;
}
