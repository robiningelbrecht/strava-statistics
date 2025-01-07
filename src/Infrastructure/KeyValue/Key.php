<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

enum Key: string
{
    case STRAVA_ACTIVITY_IMPORT = 'stravaActivityImport';
    case STRAVA_GEAR_IMPORT = 'stravaGearImport';
    case ATHLETE = 'athlete';
}
