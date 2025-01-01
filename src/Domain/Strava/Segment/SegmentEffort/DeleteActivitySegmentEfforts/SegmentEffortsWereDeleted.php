<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts;

use App\Infrastructure\Eventing\DomainEvent;

class SegmentEffortsWereDeleted extends DomainEvent
{
}
