<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\SportType;
use App\Infrastructure\ValueObject\Collection;

final class SportTypes extends Collection
{
    public function getItemClassName(): string
    {
        return SportType::class;
    }
}
