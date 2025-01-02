<?php

declare(strict_types=1);

namespace App\Domain\Strava;

enum StravaErrorStatusCode: int
{
    case TOO_MANY_REQUESTS = 429;
    case BAD_GATEWAY = 502;
    case SERVER_ERROR = 597;

    public function getErrorMessage(\Exception $e): string
    {
        return match ($this) {
            self::TOO_MANY_REQUESTS => 'You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow',
            default => sprintf('Strava API threw error: %s', $e->getMessage()),
        };
    }
}
