<?php

namespace App\Domain\Strava\Activity;

interface ActivityRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findAll(?int $limit = null): Activities;

    public function delete(Activity $activity): void;

    public function findActivityIds(): ActivityIds;
}
