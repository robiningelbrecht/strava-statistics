<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\ActivityType;

enum SportType: string
{
    case RIDE = 'Ride';
    case VIRTUAL_RIDE = 'VirtualRide';
    case RUN = 'Run';
    case TRAIL_RUN = 'TrailRun';

    public function getActivityType(): ActivityType
    {
        return match ($this) {
            SportType::RIDE, SportType::VIRTUAL_RIDE => ActivityType::RIDE,
            SportType::RUN, SportType::TRAIL_RUN => ActivityType::RUN,
        };
    }

    public function supportsReverseGeocoding(): bool
    {
        return self::RIDE === $this || self::RUN === $this;
    }

    public function supportsWeather(): bool
    {
        return self::RIDE === $this || self::RUN === $this;
    }

    public function getColor(): string
    {
        return match ($this) {
            SportType::RIDE => 'emerald-600',
            SportType::VIRTUAL_RIDE => 'orange-500',
            SportType::RUN => 'yellow-300',
            default => 'grey-500',
        };
    }

    public function isVirtual(): bool
    {
        return self::VIRTUAL_RIDE === $this;
    }
}
