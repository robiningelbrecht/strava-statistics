<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

interface ActivityWithRawDataRepository
{
    public function find(ActivityId $activityId): ActivityWithRawData;

    public function save(ActivityWithRawData $activityWithRawData): void;

    public function markActivityStreamsAsImported(ActivityId $activityId): void;
}
