<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ConsistencyChallenge: string implements TranslatableInterface
{
    case RIDE_KM_200 = 'Ride a total of 200km';
    case RIDE_KM_600 = 'Ride a total of 600km';
    case RIDE_KM_1250 = 'Ride a total of 1250km';
    case RIDE_GRAN_FONDO = 'Complete a 100km ride';
    case RIDE_CLIMBING_7500 = 'Climb a total of 7500m';
    case RUN_KM_5 = 'Complete a 5 km run.';
    case RUN_KM_10 = 'Complete a 10 km run.';
    case RUN_HALF_MARATHON = 'Complete a half marathon run.';
    case RUN_KM_100_TOTAL = 'Run a total of 100km.';
    case RUN_CLIMBING_2000 = 'Climb a total of 2000m';
    case TWO_DAYS_OF_ACTIVITY_4_WEEKS = '2 days of activity for 4 weeks';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::RIDE_KM_200 => $translator->trans('Ride a total of 200km', locale: $locale),
            self::RIDE_KM_600 => $translator->trans('Ride a total of 600km', locale: $locale),
            self::RIDE_KM_1250 => $translator->trans('Ride a total of 1250km', locale: $locale),
            self::RIDE_GRAN_FONDO => $translator->trans('Complete a 100km ride', locale: $locale),
            self::RIDE_CLIMBING_7500 => $translator->trans('Climb a total of 7500m', locale: $locale),
            self::RUN_KM_5 => $translator->trans('Complete a 5 km run.', locale: $locale),
            self::RUN_KM_10 => $translator->trans('Complete a 10 km run.', locale: $locale),
            self::RUN_HALF_MARATHON => $translator->trans('Complete a half marathon run.', locale: $locale),
            self::RUN_KM_100_TOTAL => $translator->trans('Run a total of 100km.', locale: $locale),
            self::RUN_CLIMBING_2000 => $translator->trans('Climb a total of 2000m', locale: $locale),
            self::TWO_DAYS_OF_ACTIVITY_4_WEEKS => $translator->trans('2 days of activity for 4 weeks', locale: $locale),
        };
    }
}
