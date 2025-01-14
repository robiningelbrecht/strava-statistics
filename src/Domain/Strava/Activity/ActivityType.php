<?php

namespace App\Domain\Strava\Activity;

enum ActivityType: string
{
    case RIDE = 'Ride';
    case RUN = 'Run';
    case WALK = 'Walk';
    case WATER_SPORTS = 'WaterSports';
    case WINTER_SPORTS = 'WinterSports';
    case OTHER = 'Other';

    public function getSingularLabel(): string
    {
        return ucwords(str_replace('_', ' ', strtolower($this->name)));
    }

    public function getPluralLabel(): string
    {
        if (in_array($this, [ActivityType::WATER_SPORTS, ActivityType::WINTER_SPORTS, ActivityType::OTHER])) {
            return $this->getSingularLabel();
        }

        return $this->getSingularLabel().'s';
    }

    public function supportsEddington(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, => true,
            default => false,
        };
    }

    public function supportsWeeklyDistanceStats(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, self::WALK, self::WATER_SPORTS, => true,
            default => false,
        };
    }

    public function supportsDistanceBreakdownStats(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, => true,
            default => false,
        };
    }

    public function supportsYearlyStats(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, self::WALK, self::WATER_SPORTS => true,
            default => false,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            ActivityType::RIDE => 'emerald-600',
            ActivityType::RUN => 'orange-500',
            ActivityType::WALK => 'yellow-300',
            ActivityType::WATER_SPORTS => 'blue-600',
            ActivityType::WINTER_SPORTS => 'red-600',
            default => 'slate-600',
        };
    }
}
