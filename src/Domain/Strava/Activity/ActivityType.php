<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\SportType;

enum ActivityType: string
{
    case RIDE = 'Ride';
    case VIRTUAL_RIDE = 'VirtualRide';
    case RUN = 'Run';
    case OTHER = 'Other';

    public function supportsWeather(): bool
    {
        return self::RIDE === $this || self::RUN === $this;
    }

    public function supportsReverseGeocoding(): bool
    {
        return self::RIDE === $this || self::RUN === $this;
    }

    public function isVirtual(): bool
    {
        return self::VIRTUAL_RIDE === $this;
    }

    public function getSportType(): SportType
    {
        return match ($this) {
            ActivityType::RIDE, ActivityType::VIRTUAL_RIDE => SportType::RIDE,
            ActivityType::RUN => SportType::RUN,
            ActivityType::OTHER => SportType::OTHER,
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            ActivityType::RIDE => 'bike',
            ActivityType::RUN => 'run',
            ActivityType::OTHER => 'question-mark',
            default => throw new \RuntimeException(sprintf('No icon found for activityType %s', $this->value)),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            ActivityType::RIDE => 'emerald-600',
            ActivityType::VIRTUAL_RIDE => 'orange-500',
            ActivityType::RUN => 'yellow-300',
            ActivityType::OTHER => 'grey-500',
        };
    }
}
