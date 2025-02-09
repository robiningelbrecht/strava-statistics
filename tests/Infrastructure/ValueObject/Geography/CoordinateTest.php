<?php

namespace App\Tests\Infrastructure\ValueObject\Geography;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class CoordinateTest extends TestCase
{
    use MatchesSnapshots;

    public function testCreateFromOptionalLatAndLng(): void
    {
        $coordinate = Coordinate::createFromOptionalLatAndLng(
            latitude: Latitude::fromString('3'),
            longitude: Longitude::fromString('2'),
        );

        $this->assertEquals(
            Latitude::fromString('3'),
            $coordinate->getLatitude()
        );
        $this->assertEquals(
            Longitude::fromString('2'),
            $coordinate->getLongitude()
        );
        $this->assertNull(Coordinate::createFromOptionalLatAndLng(
            latitude: Latitude::fromString('3'),
            longitude: null
        ));
        $this->assertNull(Coordinate::createFromOptionalLatAndLng(
            latitude: null,
            longitude: Longitude::fromString('2')
        ));
    }

    public function testJsonSerialize(): void
    {
        $coordinate = Coordinate::createFromOptionalLatAndLng(
            latitude: Latitude::fromString('3'),
            longitude: Longitude::fromString('2'),
        );

        $this->assertMatchesJsonSnapshot(Json::encode($coordinate));
    }
}
