<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Infrastructure\ValueObject\Collection;

final class ActivitySplits extends Collection
{
    public function getItemClassName(): string
    {
        return ActivitySplit::class;
    }
}
