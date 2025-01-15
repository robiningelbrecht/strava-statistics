<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\LeafletMap;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityDetails
{
    use ProvideTimeFormats;

    private ?string $gearName = null;
    private ?int $maxCadence = null;
    /** @var array<mixed> */
    private array $bestPowerOutputs = [];

    public function __construct(
        private readonly ActivityId $activityId,
        private readonly SerializableDateTime $startDateTime,
        private readonly SportType $sportType,
        private readonly Name $name,
        private readonly string $description,
        private readonly Kilometer $distance,
        private readonly Meter $elevation,
        private readonly ?Latitude $latitude,
        private readonly ?Longitude $longitude,
        private readonly int $calories,
        private readonly ?int $averagePower,
        private readonly ?int $maxPower,
        private readonly KmPerHour $averageSpeed,
        private readonly KmPerHour $maxSpeed,
        private readonly ?int $averageHeartRate,
        private readonly ?int $maxHeartRate,
        private readonly ?int $averageCadence,
        private readonly int $movingTimeInSeconds,
        private readonly int $kudoCount,
        private readonly int $totalImageCount,
        private readonly ?string $deviceName,
        /** @var array<string> */
        private readonly array $localImagePaths,
        private readonly ?string $polyline,
        private readonly ?Location $location,
        private readonly string $segmentEfforts,
        private readonly string $weather,
        private readonly ?GearId $gearId,
    ) {
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
        return $this->latitude;
    }

    public function getLongitude(): ?Longitude
    {
        return $this->longitude;
    }

    public function getKudoCount(): int
    {
        return $this->kudoCount;
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function enrichWithGearName(string $gearName): void
    {
        $this->gearName = $gearName;
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
        $decodedWeather = Json::decode($this->weather);
        $hour = $this->getStartDate()->getHourWithoutLeadingZero();
        if (!empty($decodedWeather['hourly']['time'][$hour])) {
            // Use weather known for the given hour.
            $weather = [];
            foreach ($decodedWeather['hourly'] as $metric => $values) {
                $weather[$metric] = $values[$hour];
            }

            return Weather::fromMap($weather);
        }

        if (!empty($decodedWeather['daily'])) {
            // Use weather known for that day.
            $weather = [];
            foreach ($decodedWeather['daily'] as $metric => $values) {
                $weather[$metric] = reset($values);
            }

            return Weather::fromMap($weather);
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getLocalImagePaths(): array
    {
        return $this->localImagePaths;
    }

    public function getTotalImageCount(): int
    {
        return $this->totalImageCount;
    }

    public function getName(): string
    {
        return trim(str_replace('Zwift - ', '', (string) $this->name));
    }

    public function getDescription(): string
    {
        return trim($this->description);
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function getElevation(): Meter
    {
        return $this->elevation;
    }

    public function getCalories(): int
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

    public function getPolylineSummary(): ?string
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
        if (!$this->getLatitude() || !$this->getLongitude()) {
            return null;
        }
        if ($this->getSportType()->supportsReverseGeocoding()) {
            return LeafletMap::REAL_WORLD;
        }
        if (!$this->isZwiftRide()) {
            return LeafletMap::REAL_WORLD;
        }

        return LeafletMap::forZwiftStartingCoordinate(Coordinate::createFromLatAndLng(
            latitude: $this->getLatitude(),
            longitude: $this->getLongitude(),
        ));
    }

    /**
     * @return array<mixed>
     */
    public function getSegmentEfforts(): array
    {
        return !empty($this->segmentEfforts) ? Json::decode($this->segmentEfforts) : [];
    }

    public function getLocation(): ?Location
    {
        return $this->location;
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
}
