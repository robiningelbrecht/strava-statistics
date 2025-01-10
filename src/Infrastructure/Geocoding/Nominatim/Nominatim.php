<?php

declare(strict_types=1);

namespace App\Infrastructure\Geocoding\Nominatim;

use App\Infrastructure\ValueObject\Geography\Coordinate;

interface Nominatim
{
    public function reverseGeocode(Coordinate $coordinate): Location;
}
