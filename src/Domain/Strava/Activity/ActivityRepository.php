<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Gear\GearIds;

interface ActivityRepository
{
    public function add(Activity $activity): void;

    public function update(Activity $activity): void;

    public function delete(Activity $activity): void;

    public function find(ActivityId $activityId): Activity;

    public function findAll(?int $limit = null): Activities;

    public function findActivityIds(): ActivityIds;

    public function findUniqueGearIds(): GearIds;

    public function findMostRiddenState(): ?string;
}
