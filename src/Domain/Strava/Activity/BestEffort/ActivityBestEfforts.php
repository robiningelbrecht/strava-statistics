<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Infrastructure\ValueObject\Collection;

final class ActivityBestEfforts extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityBestEffort::class;
    }
}
