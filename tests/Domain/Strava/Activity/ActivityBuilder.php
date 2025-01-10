<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityBuilder
{
    private ActivityId $activityId;
    private SerializableDateTime $startDateTime;
    private SportType $sportType;
    private array $data;
    private array $weather;
    private ?GearId $gearId;
    private ?Location $location;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('903645');
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->sportType = SportType::RIDE;
        $this->data = [
            'kudos_count' => 1,
            'name' => 'Test activity',
        ];
        $this->weather = [];
        $this->gearId = null;
        $this->location = null;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Activity
    {
        return Activity::fromState(
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            sportType: $this->sportType,
            data: $this->data,
            location: $this->location,
            weather: $this->weather,
            gearId: $this->gearId
        );
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withGearId(GearId $gearId): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    public function withoutGearId(): self
    {
        $this->gearId = null;

        return $this;
    }

    public function withStartDateTime(SerializableDateTime $startDateTime): self
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function withWeather(array $weather): self
    {
        $this->weather = $weather;

        return $this;
    }

    public function withAddress(Location $address): self
    {
        $this->location = $address;

        return $this;
    }

    public function withSportType(SportType $sportType): self
    {
        $this->sportType = $sportType;

        return $this;
    }
}
