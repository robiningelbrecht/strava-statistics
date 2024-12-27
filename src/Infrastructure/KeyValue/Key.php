<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

enum Key: string
{
    case STRAVA_ACTIVITY_IMPORT = 'stravaActivityImport';
    case STRAVA_GEAR_IMPORT = 'stravaGearImport';
    case STRAVA_LIMITS_HAVE_BEEN_REACHED = 'stravaLimitsHaveBeenReached';
    case ATHLETE_ID = 'athlete_id';
}
