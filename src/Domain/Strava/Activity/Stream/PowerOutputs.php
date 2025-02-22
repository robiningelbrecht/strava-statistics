<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<PowerOutput>
 */
final class PowerOutputs extends Collection
{
    public function getItemClassName(): string
    {
        return PowerOutput::class;
    }
}
