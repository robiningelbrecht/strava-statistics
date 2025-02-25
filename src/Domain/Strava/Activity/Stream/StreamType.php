<?php

namespace App\Domain\Strava\Activity\Stream;

enum StreamType: string
{
    case TIME = 'time';
    case LAT_LNG = 'latlng';
    case WATTS = 'watts';
    case HEART_RATE = 'heartrate';
    case CADENCE = 'cadence';
    case ALTITUDE = 'altitude';
    case TEMP = 'temp';
    case HACK = 'hack';
}
