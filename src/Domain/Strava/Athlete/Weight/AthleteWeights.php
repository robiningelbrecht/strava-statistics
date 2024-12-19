<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\ValueObject\Collection;

final class AthleteWeights extends Collection
{
    public function getItemClassName(): string
    {
        return AthleteWeight::class;
    }
}
