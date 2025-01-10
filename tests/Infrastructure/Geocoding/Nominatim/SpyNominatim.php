<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Geocoding\Nominatim;

use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Geocoding\Nominatim\Nominatim;
use App\Infrastructure\ValueObject\Geography\Coordinate;

class SpyNominatim implements Nominatim
{
    public function reverseGeocode(Coordinate $coordinate): Location
    {
        return Location::fromState([
            'country_code' => 'be',
            'state' => 'West Vlaanderen',
        ]);
    }
}
