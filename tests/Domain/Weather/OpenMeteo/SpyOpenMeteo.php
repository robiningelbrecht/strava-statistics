<?php

declare(strict_types=1);

namespace App\Tests\Domain\Weather\OpenMeteo;

use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class SpyOpenMeteo implements OpenMeteo
{
    public function getWeatherStats(Coordinate $coordinate, SerializableDateTime $date): array
    {
        return [];
    }
}
