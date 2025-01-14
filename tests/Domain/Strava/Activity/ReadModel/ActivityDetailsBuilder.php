<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity\ReadModel;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityDetailsBuilder
{
    private readonly ActivityId $activityId;
    private SerializableDateTime $startDateTime;
    private readonly SportType $sportType;
    private readonly Name $name;
    private readonly string $description;
    private readonly Kilometer $distance;
    private readonly Meter $elevation;
    private readonly ?Latitude $latitude;
    private readonly ?Longitude $longitude;
    private readonly int $calories;
    private ?int $averagePower;
    private readonly ?int $maxPower;
    private readonly KmPerHour $averageSpeed;
    private readonly KmPerHour $maxSpeed;
    private ?int $averageHeartRate;
    private readonly ?int $maxHeartRate;
    private readonly ?int $averageCadence;
    private int $movingTimeInSeconds;
    private readonly int $kudoCount;
    private readonly int $totalImageCount;
    private readonly ?string $deviceName;
    /** @var array<string> */
    private readonly array $localImagePaths;
    private readonly ?string $polyline;
    private readonly ?Location $location;
    private readonly string $segmentEfforts;
    private readonly string $weather;
    private readonly ?GearId $gearId;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('903645');
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->sportType = SportType::RIDE;
        $this->kudoCount = 1;
        $this->name = Name::fromString('Test activity');
        $this->description = '';
        $this->distance = Kilometer::from(10);
        $this->elevation = Meter::from(0);
        $this->latitude = null;
        $this->longitude = null;
        $this->calories = 0;
        $this->averagePower = null;
        $this->maxPower = null;
        $this->averageSpeed = KmPerHour::from(0);
        $this->maxSpeed = KmPerHour::from(0);
        $this->averageHeartRate = null;
        $this->maxHeartRate = null;
        $this->averageCadence = null;
        $this->movingTimeInSeconds = 10;
        $this->totalImageCount = 0;
        $this->deviceName = null;
        $this->localImagePaths = [];
        $this->polyline = null;
        $this->segmentEfforts = '';
        $this->weather = '';
        $this->gearId = null;
        $this->location = null;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ActivityDetails
    {
        return new ActivityDetails(
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            sportType: $this->sportType,
            name: $this->name,
            description: $this->description,
            distance: $this->distance,
            elevation: $this->elevation,
            latitude: $this->latitude,
            longitude: $this->longitude,
            calories: $this->calories,
            averagePower: $this->averagePower,
            maxPower: $this->maxPower,
            averageSpeed: $this->averageSpeed,
            maxSpeed: $this->maxSpeed,
            averageHeartRate: $this->averageHeartRate,
            maxHeartRate: $this->maxHeartRate,
            averageCadence: $this->averageCadence,
            movingTimeInSeconds: $this->movingTimeInSeconds,
            kudoCount: $this->kudoCount,
            totalImageCount: $this->totalImageCount,
            deviceName: $this->deviceName,
            localImagePaths: $this->localImagePaths,
            polyline: $this->polyline,
            location: $this->location,
            segmentEfforts: $this->segmentEfforts,
            weather: $this->weather,
            gearId: $this->gearId,
        );
    }

    public function withStartDateTime(SerializableDateTime $startDateTime): self
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function withAveragePower(int $averagePower): self
    {
        $this->averagePower = $averagePower;

        return $this;
    }

    public function withMovingTimeInSeconds(int $movingTimeInSeconds): self
    {
        $this->movingTimeInSeconds = $movingTimeInSeconds;

        return $this;
    }

    public function withAverageHeartRate(int $averageHeartRate): self
    {
        $this->averageHeartRate = $averageHeartRate;

        return $this;
    }
}
