<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\LeafletMap;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Eventing\RecordsEvents;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'Activity_startDateTimeIndex', columns: ['startDateTime'])]
final class Activity
{
    use RecordsEvents;
    use ProvideTimeFormats;

    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    private ?int $maxCadence = null;
    /** @var array<mixed> */
    private array $bestPowerOutputs = [];

    #[ORM\Column(type: 'json', nullable: true)]
    // @phpstan-ignore-next-line
    private readonly array $data;

    /**
     * @param array<string> $localImagePaths
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'string')]
        private readonly SportType $sportType,
        #[ORM\Column(type: 'string')]
        private string $name,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $description,
        #[ORM\Column(type: 'integer')]
        private readonly Kilometer $distance,
        #[ORM\Column(type: 'integer')]
        private Meter $elevation,
        #[ORM\Embedded(class: Coordinate::class)]
        private readonly ?Coordinate $startingCoordinate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $calories,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averagePower,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxPower,
        #[ORM\Column(type: 'float')]
        private readonly KmPerHour $averageSpeed,
        #[ORM\Column(type: 'float')]
        private readonly KmPerHour $maxSpeed,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $maxHeartRate,
        #[ORM\Column(type: 'integer', nullable: true)]
        private readonly ?int $averageCadence,
        #[ORM\Column(type: 'integer')]
        private readonly int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private int $kudoCount,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly ?string $deviceName,
        #[ORM\Column(type: 'integer')]
        private readonly int $totalImageCount,
        #[ORM\Column(type: 'text', nullable: true)]
        private array $localImagePaths,
        #[ORM\Column(type: 'text', nullable: true)]
        private readonly ?string $polyline,
        #[ORM\Column(type: 'json', nullable: true)]
        private ?Location $location,
        #[ORM\Column(type: 'json', nullable: true)]
        private ?string $weather,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?GearId $gearId,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $gearName,
    ) {
    }

    /**
     * @param array<mixed> $rawData
     */
    public static function createFromRawData(
        array $rawData,
        ?GearId $gearId,
        ?string $gearName,
    ): self {
        $startDate = SerializableDateTime::createFromFormat(
            format: Activity::DATE_TIME_FORMAT,
            datetime: $rawData['start_date_local'],
            timezone: SerializableTimezone::default(),
        );

        return self::fromState(
            activityId: ActivityId::fromUnprefixed((string) $rawData['id']),
            startDateTime: $startDate,
            sportType: SportType::from($rawData['sport_type']),
            name: $rawData['name'],
            description: $rawData['description'],
            distance: Kilometer::from(round($rawData['distance'] / 1000, 3)),
            elevation: Meter::from(round($rawData['total_elevation_gain'])),
            startingCoordinate: Coordinate::createFromOptionalLatAndLng(
                Latitude::fromOptionalString($rawData['start_latlng'][0] ?? null),
                Longitude::fromOptionalString($rawData['start_latlng'][1] ?? null),
            ),
            calories: (int) ($rawData['calories'] ?? 0),
            averagePower: isset($rawData['average_watts']) ? (int) $rawData['average_watts'] : null,
            maxPower: isset($rawData['max_watts']) ? (int) $rawData['max_watts'] : null,
            averageSpeed: KmPerHour::from(round($rawData['average_speed'] * 3.6, 3)),
            maxSpeed: KmPerHour::from(round($rawData['max_speed'] * 3.6, 3)),
            averageHeartRate: isset($rawData['average_heartrate']) ? (int) round($rawData['average_heartrate']) : null,
            maxHeartRate: isset($rawData['max_heartrate']) ? (int) round($rawData['max_heartrate']) : null,
            averageCadence: isset($rawData['average_cadence']) ? (int) round($rawData['average_cadence']) : null,
            movingTimeInSeconds: $rawData['moving_time'] ?? 0,
            kudoCount: $rawData['kudos_count'] ?? 0,
            deviceName: $rawData['device_name'] ?? null,
            totalImageCount: $rawData['total_photo_count'] ?? 0,
            localImagePaths: [],
            polyline: $rawData['map']['summary_polyline'] ?? null,
            location: null,
            weather: null,
            gearId: $gearId,
            gearName: $gearName
        );
    }

    /**
     * @param array<string> $localImagePaths
     */
    public static function fromState(
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        SportType $sportType,
        string $name,
        ?string $description,
        Kilometer $distance,
        Meter $elevation,
        ?Coordinate $startingCoordinate,
        ?int $calories,
        ?int $averagePower,
        ?int $maxPower,
        KmPerHour $averageSpeed,
        KmPerHour $maxSpeed,
        ?int $averageHeartRate,
        ?int $maxHeartRate,
        ?int $averageCadence,
        int $movingTimeInSeconds,
        int $kudoCount,
        ?string $deviceName,
        int $totalImageCount,
        array $localImagePaths,
        ?string $polyline,
        ?Location $location,
        ?string $weather,
        ?GearId $gearId,
        ?string $gearName,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            sportType: $sportType,
            name: $name,
            description: $description,
            distance: $distance,
            elevation: $elevation,
            startingCoordinate: $startingCoordinate,
            calories: $calories,
            averagePower: $averagePower,
            maxPower: $maxPower,
            averageSpeed: $averageSpeed,
            maxSpeed: $maxSpeed,
            averageHeartRate: $averageHeartRate,
            maxHeartRate: $maxHeartRate,
            averageCadence: $averageCadence,
            movingTimeInSeconds: $movingTimeInSeconds,
            kudoCount: $kudoCount,
            deviceName: $deviceName,
            totalImageCount: $totalImageCount,
            localImagePaths: $localImagePaths,
            polyline: $polyline,
            location: $location,
            weather: $weather,
            gearId: $gearId,
            gearName: $gearName
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

    public function getStartingCoordinate(): ?Coordinate
    {
        return $this->startingCoordinate;
    }

    public function getKudoCount(): int
    {
        return $this->kudoCount;
    }

    public function updateKudoCount(int $count): self
    {
        $this->kudoCount = $count;

        return $this;
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function updateGear(
        ?GearId $gearId = null,
        ?string $gearName = null,
    ): self {
        $this->gearId = $gearId;
        $this->gearName = $gearName;

        return $this;
    }

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function hasDetailedPowerData(): bool
    {
        return !empty($this->bestPowerOutputs);
    }

    public function getBestAveragePowerForTimeInterval(int $timeInterval): ?PowerOutput
    {
        return $this->bestPowerOutputs[$timeInterval] ?? null;
    }

    /**
     * @param array<mixed> $bestPowerOutputs
     */
    public function enrichWithBestPowerOutputs(array $bestPowerOutputs): void
    {
        $this->bestPowerOutputs = $bestPowerOutputs;
    }

    public function getWeather(): ?Weather
    {
        if (!$this->weather) {
            return null;
        }
        if ($decodedWeather = Json::decode($this->weather)) {
            return Weather::fromState($decodedWeather);
        }

        return null;
    }

    public function updateWeather(?Weather $weather): void
    {
        $this->weather = Json::encode($weather);
    }

    /**
     * @return array<string>
     */
    public function getLocalImagePaths(): array
    {
        return $this->localImagePaths;
    }

    /**
     * @param array<string> $localImagePaths
     */
    public function updateLocalImagePaths(array $localImagePaths): void
    {
        $this->localImagePaths = $localImagePaths;
    }

    public function getTotalImageCount(): int
    {
        return $this->totalImageCount;
    }

    public function getName(): string
    {
        return trim(str_replace('Zwift - ', '', $this->name));
    }

    public function updateName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return trim($this->description ?? '');
    }

    public function updateDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function updateElevation(Meter $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function getAveragePower(): ?int
    {
        return $this->averagePower;
    }

    public function getMaxPower(): ?int
    {
        return $this->maxPower;
    }

    public function getAverageSpeed(): KmPerHour
    {
        return $this->averageSpeed;
    }

    public function getMaxSpeed(): KmPerHour
    {
        return $this->maxSpeed;
    }

    public function getAverageHeartRate(): ?int
    {
        return $this->averageHeartRate;
    }

    public function getMaxHeartRate(): ?int
    {
        return $this->maxHeartRate;
    }

    public function getAverageCadence(): ?int
    {
        return $this->averageCadence;
    }

    public function getMaxCadence(): ?int
    {
        return $this->maxCadence;
    }

    public function enrichWithMaxCadence(int $maxCadence): void
    {
        $this->maxCadence = $maxCadence;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->formatDurationForHumans($this->getMovingTimeInSeconds());
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/activities/'.$this->getId()->toUnprefixedString();
    }

    public function getPolyline(): ?string
    {
        return $this->polyline;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function isZwiftRide(): bool
    {
        return 'zwift' === strtolower($this->getDeviceName() ?? '');
    }

    public function isRouvyRide(): bool
    {
        return 'rouvy' === strtolower($this->getDeviceName() ?? '');
    }

    public function getLeafletMap(): ?LeafletMap
    {
        if (!$this->getStartingCoordinate()) {
            return null;
        }
        if ($this->getSportType()->supportsReverseGeocoding()) {
            return LeafletMap::REAL_WORLD;
        }
        if (!$this->isZwiftRide()) {
            return LeafletMap::REAL_WORLD;
        }

        return LeafletMap::forZwiftStartingCoordinate($this->getStartingCoordinate());
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function updateLocation(?Location $location = null): void
    {
        $this->location = $location;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return [$this->getName()];
    }

    /**
     * @return array<string, string>
     */
    public function getFilterables(): array
    {
        return [
            'sportType' => $this->getSportType()->value,
        ];
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortables(): array
    {
        return array_filter([
            'start-date' => $this->getStartDate()->getTimestamp(),
            'distance' => $this->getDistance()->toFloat(),
            'elevation' => $this->getElevation()->toFloat(),
            'moving-time' => $this->getMovingTimeInSeconds(),
            'power' => $this->getAveragePower(),
            'speed' => round($this->getAverageSpeed()->toFloat(), 1),
            'heart-rate' => $this->getAverageHeartRate(),
            'calories' => $this->getCalories(),
        ]);
    }

    public function delete(): void
    {
        $this->recordThat(new ActivityWasDeleted($this->getId()));
    }
}
