<?php

namespace App\Domain\Strava\Activity\WriteModel;

use App\Domain\Measurement\Length\Meter;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWasDeleted;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Eventing\RecordsEvents;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Activity
{
    use RecordsEvents;

    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly SportType $sportType,
        #[ORM\Column(type: 'json')]
        private array $data,
        #[ORM\Column(type: 'json', nullable: true)]
        private ?Location $location = null,
        #[ORM\Column(type: 'json', nullable: true)]
        private array $weather = [],
        #[ORM\Column(type: 'string', nullable: true)]
        private ?GearId $gearId = null,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        SportType $sportType,
        array $data,
        ?GearId $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            sportType: $sportType,
            data: $data,
            gearId: $gearId
        );
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    public static function fromState(
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        SportType $sportType,
        array $data,
        ?Location $location = null,
        array $weather = [],
        ?GearId $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            sportType: $sportType,
            data: $data,
            location: $location,
            weather: $weather,
            gearId: $gearId
        );
    }

    public function getId(): ActivityId
    {
        return $this->activityId;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getLatitude(): ?Latitude
    {
        return Latitude::fromOptionalString($this->data['start_latlng'][0] ?? null);
    }

    public function getLongitude(): ?Longitude
    {
        return Longitude::fromOptionalString($this->data['start_latlng'][1] ?? null);
    }

    public function updateKudoCount(int $count): self
    {
        $this->data['kudos_count'] = $count;

        return $this;
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function updateGearId(?GearId $gearId = null): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    /**
     * @param array<mixed> $weather
     */
    public function updateWeather(array $weather): void
    {
        $this->weather = $weather;
    }

    /**
     * @return array<mixed>
     */
    public function getAllWeatherData(): array
    {
        return $this->weather;
    }

    public function getTotalImageCount(): int
    {
        return $this->data['total_photo_count'] ?? 0;
    }

    /**
     * @param array<string> $localImagePaths
     */
    public function updateLocalImagePaths(array $localImagePaths): void
    {
        $this->data['localImagePaths'] = $localImagePaths;
    }

    public function getName(): string
    {
        return trim(str_replace('Zwift - ', '', $this->data['name']));
    }

    public function updateName(string $name): self
    {
        $this->data['name'] = $name;

        return $this;
    }

    public function updateDescription(string $description): self
    {
        $this->data['description'] = $description;

        return $this;
    }

    public function updateElevation(Meter $elevation): self
    {
        $this->data['total_elevation_gain'] = $elevation;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function delete(): void
    {
        $this->recordThat(new ActivityWasDeleted($this->getId()));
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function updateLocation(?Location $location = null): void
    {
        $this->location = $location;
    }
}
