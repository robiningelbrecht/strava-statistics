<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\SportType;

use App\Domain\Strava\Activity\ActivityType;

enum SportType: string
{
    // Cycle.
    case RIDE = 'Ride';
    case MOUNTAIN_BIKE_RIDE = 'MountainBikeRide';
    case GRAVEL_RIDE = 'GravelRide';
    case E_BIKE_RIDE = 'EBikeRide';
    case E_MOUNTAIN_BIKE_RIDE = 'EMountainBikeRide';
    case VIRTUAL_RIDE = 'VirtualRide';
    case VELO_MOBILE = 'Velomobile';
    // Run.
    case RUN = 'Run';
    case TRAIL_RUN = 'TrailRun';
    case VIRTUAL_RUN = 'VirtualRun';
    // Walk
    case WALK = 'Walk';
    case HIKE = 'Hike';
    // Water sports.
    case CANOEING = 'Canoeing';
    case KAYAKING = 'Kayaking';
    case KITE_SURF = 'Kitesurf';
    case ROWING = 'Rowing';
    case STAND_UP_PADDLING = 'StandUpPaddling';
    case SURFING = 'Surfing';
    case SWIM = 'Swim';
    case WIND_SURF = 'WindSurf';
    // Winter sports.
    case BACK_COUNTRY_SKI = 'BackcountrySki';
    case ALPINE_SKI = 'AlpineSki';
    case NORDIC_SKI = 'NordicSki';
    case ICE_SKATE = 'IceSkate';
    case SNOWBOARD = 'Snowboard';
    case SNOWSHOE = 'Snowshoe';
    // Other sports.
    case BADMINTON = 'Badminton';
    case CROSSFIT = 'Crossfit';
    case ELLIPTICAL = 'Elliptical';
    case GOLF = 'Golf';
    case INLINE_SKATE = 'InlineSkate';
    case HAND_CYCLE = 'Handcycle';
    case HIIT = 'HighIntensityIntervalTraining';
    case PICKLE_BALL = 'Pickleball';
    case PILATES = 'Pilates';
    case RACQUET_BALL = 'Racquetball';
    case ROCK_CLIMBING = 'RockClimbing';
    case ROLLER_SKI = 'RollerSki';
    case VIRTUAL_ROW = 'VirtualRow';
    case SAIL = 'Sail';
    case SKATEBOARD = 'Skateboard';
    case SOCCER = 'Soccer';
    case SQUASH = 'Squash';
    case STAIR_STEPPER = 'StairStepper';
    case TABLE_TENNIS = 'TableTennis';
    case TENNIS = 'Tennis';
    case WEIGHT_TRAINING = 'WeightTraining';
    case WHEELCHAIR = 'Wheelchair';
    case WORKOUT = 'Workout';
    case YOGA = 'Yoga';

    public function getSingularLabel(): string
    {
        if (self::HIIT === $this) {
            return 'HIIT';
        }

        return ucwords(str_replace('_', ' ', strtolower($this->name)));
    }

    public function getPluralLabel(): string
    {
        if (str_ends_with($this->getSingularLabel(), 's')) {
            return $this->getSingularLabel();
        }

        if (in_array($this->getActivityType(), [ActivityType::WATER_SPORTS, ActivityType::WINTER_SPORTS, ActivityType::OTHER])) {
            return $this->getSingularLabel();
        }

        return $this->getSingularLabel().'s';
    }

    public function getActivityType(): ActivityType
    {
        return match ($this) {
            // RIDE.
            SportType::RIDE, SportType::MOUNTAIN_BIKE_RIDE,
            SportType::GRAVEL_RIDE, SportType::E_BIKE_RIDE,
            SportType::E_MOUNTAIN_BIKE_RIDE, SportType::VIRTUAL_RIDE,
            SportType::VELO_MOBILE => ActivityType::RIDE,
            // RUN.
            SportType::RUN, SportType::TRAIL_RUN, SportType::VIRTUAL_RUN => ActivityType::RUN,
            // WALK.
            SportType::WALK, SportType::HIKE => ActivityType::WALK,
            // WATER.
            SportType::CANOEING, SportType::KAYAKING, SportType::KITE_SURF,
            SportType::ROWING, SportType::STAND_UP_PADDLING,
            SportType::SURFING, SportType::SWIM, SportType::WIND_SURF => ActivityType::WATER_SPORTS,
            // WINTER.
            SportType::BACK_COUNTRY_SKI, SportType::ALPINE_SKI, SportType::NORDIC_SKI,
            SportType::ICE_SKATE, SportType::SNOWBOARD, SportType::SNOWSHOE => ActivityType::WINTER_SPORTS,
            // OTHER.
            default => ActivityType::OTHER,
        };
    }

    public function getSvgIcon(): string
    {
        return match ($this) {
            SportType::ALPINE_SKI, SportType::BACK_COUNTRY_SKI,
            SportType::NORDIC_SKI, SportType::ROLLER_SKI => 'ski',
            SportType::RIDE, SportType::VIRTUAL_RIDE => 'bike-ride',
            SportType::CROSSFIT, SportType::WEIGHT_TRAINING => 'weight-training',
            SportType::RUN, SportType::VIRTUAL_RUN => 'run',
            SportType::WORKOUT, SportType::ELLIPTICAL => 'workout',
            SportType::CANOEING, SportType::KAYAKING => 'canoeing',
            SportType::SAIL, SportType::WIND_SURF => 'sail',
            default => str_replace('_', '-', strtolower($this->name)),
        };
    }

    public function supportsReverseGeocoding(): bool
    {
        return !in_array($this, [
            self::VIRTUAL_RIDE,
            self::VIRTUAL_RUN,
            self::VIRTUAL_ROW,
            self::BADMINTON,
            self::CROSSFIT,
            self::ELLIPTICAL,
            self::INLINE_SKATE,
            self::HIIT,
            self::PICKLE_BALL,
            self::PILATES,
            self::RACQUET_BALL,
            self::SQUASH,
            self::TABLE_TENNIS,
            self::WEIGHT_TRAINING,
            self::WORKOUT,
            self::SWIM,
            self::ICE_SKATE,
            self::YOGA,
        ]);
    }

    public function supportsWeather(): bool
    {
        return !in_array($this, [
            self::VIRTUAL_RIDE,
            self::VIRTUAL_RUN,
            self::VIRTUAL_ROW,
            self::BADMINTON,
            self::CROSSFIT,
            self::ELLIPTICAL,
            self::INLINE_SKATE,
            self::HIIT,
            self::PICKLE_BALL,
            self::PILATES,
            self::RACQUET_BALL,
            self::SQUASH,
            self::TABLE_TENNIS,
            self::WEIGHT_TRAINING,
            self::WORKOUT,
            self::SWIM,
            self::ICE_SKATE,
            self::YOGA,
        ]);
    }

    public function isVirtualVirtualRide(): bool
    {
        return self::VIRTUAL_RIDE === $this;
    }
}
