<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

interface ActivitySplitRepository
{
    public function findBy(ActivityId $activityId, UnitSystem $unitSystem): ActivitySplits;

    public function add(ActivitySplit $activitySplit): void;

    public function isImportedForActivity(ActivityId $activityId): bool;

    public function deleteForActivity(ActivityId $activityId): void;
}
