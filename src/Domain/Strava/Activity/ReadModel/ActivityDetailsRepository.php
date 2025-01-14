<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityId;

interface ActivityDetailsRepository
{
    public function find(ActivityId $activityId): ActivityDetails;

    public function findAll(?int $limit = null): Activities;

    public function findMostActiveState(): ?string;
}
