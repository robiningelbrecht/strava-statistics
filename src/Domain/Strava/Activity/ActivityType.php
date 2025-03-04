<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ActivityType: string implements TranslatableInterface
{
    case RIDE = 'Ride';
    case RUN = 'Run';
    case WALK = 'Walk';
    case WATER_SPORTS = 'WaterSports';
    case WINTER_SPORTS = 'WinterSports';
    case SKATING = 'Skating';
    case RACQUET_PADDLE_SPORTS = 'RacquetPaddleSports';
    case FITNESS = 'Fitness';
    case MIND_BODY_SPORTS = 'MindBodySports';
    case OUTDOOR_SPORTS = 'OutdoorAdventureSports';
    case ADAPTIVE_INCLUSIVE_SPORTS = 'AdaptiveInclusiveSports';
    case OTHER = 'Other';

    public function getTemplateName(): string
    {
        return str_replace(['_'], '-', strtolower($this->name));
    }

    public function getSportTypes(): SportTypes
    {
        $sportTypes = SportTypes::empty();

        foreach (SportType::cases() as $sportType) {
            if ($sportType->getActivityType() !== $this) {
                continue;
            }
            $sportTypes->add($sportType);
        }

        return $sportTypes;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::RIDE => $translator->trans('Rides', locale: $locale),
            self::RUN => $translator->trans('Runs', locale: $locale),
            self::WALK => $translator->trans('Walks', locale: $locale),
            self::WATER_SPORTS => $translator->trans('Water Sports', locale: $locale),
            self::WINTER_SPORTS => $translator->trans('Winter Sports', locale: $locale),
            self::SKATING => $translator->trans('Skating', locale: $locale),
            self::RACQUET_PADDLE_SPORTS => $translator->trans('Racquet & Paddle Sports', locale: $locale),
            self::FITNESS => $translator->trans('Fitness', locale: $locale),
            self::MIND_BODY_SPORTS => $translator->trans('Mind & Body Sports', locale: $locale),
            self::OUTDOOR_SPORTS => $translator->trans('Outdoor Sports', locale: $locale),
            self::ADAPTIVE_INCLUSIVE_SPORTS => $translator->trans('Adaptive & Inclusive Sports', locale: $locale),
            self::OTHER => $translator->trans('Other', locale: $locale),
        };
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
        return ActivityType::OTHER !== $this;
    }

    public function supportsHeartRateOverTimeChart(): bool
    {
        return match ($this) {
            self::RUN, self::WALK => true,
            default => false,
        };
    }

    public function supportsPowerDistributionChart(): bool
    {
        return match ($this) {
            self::RIDE => true,
            default => false,
        };
    }

    public function supportsDistanceBreakdownStats(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, self::SKATING => true,
            default => false,
        };
    }

    public function supportsYearlyStats(): bool
    {
        return match ($this) {
            self::RUN, self::RIDE, self::WALK, self::WATER_SPORTS, self::SKATING => true,
            default => false,
        };
    }

    public function getDistancePrecision(): int
    {
        return match ($this) {
            self::RUN, self::WALK, self::WATER_SPORTS => 2,
            default => 0,
        };
    }

    public function prefersPaceOverSpeed(): bool
    {
        return match ($this) {
            self::RUN, self::WALK => true,
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
