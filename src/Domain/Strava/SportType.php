<?php

declare(strict_types=1);

namespace App\Domain\Strava;

enum SportType: string
{
    case RIDE = 'Ride';
    case RUN = 'Run';
    case OTHER = 'Other';

    public function supportsEddington(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE => true,
            default => false,
        };
    }
}
