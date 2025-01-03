<?php

namespace App\Domain\Strava\Activity;

enum ActivityType: string
{
    case RIDE = 'Ride';
    case RUN = 'Run';
    case SWIM = 'Swim';

    public function supportsEddington(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, => true,
            default => false,
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            ActivityType::RIDE => 'bike',
            ActivityType::RUN => 'run',
            default => throw new \RuntimeException(sprintf('No icon found for activityType %s', $this->value)),
        };
    }
}
