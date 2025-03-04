<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\CQRS\DomainCommand;

final class DeleteActivitySegmentEfforts extends DomainCommand
{
    public function __construct(
        private readonly ActivityId $activityId,
    ) {
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }
}
