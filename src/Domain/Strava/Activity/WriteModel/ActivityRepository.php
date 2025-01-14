<?php

namespace App\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Gear\GearIds;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function add(Activity $activity): void;

    public function update(Activity $activity): void;

    public function delete(Activity $activity): void;

    public function findActivityIds(): ActivityIds;

    public function findUniqueGearIds(): GearIds;
}
