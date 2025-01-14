<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\LeafletMap;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Time\TimeFormatter;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityDetails
{
    use TimeFormatter;

    private ?string $gearName = null;
    /** @var array<mixed> */
    private array $bestPowerOutputs = [];

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    public function __construct(
        private readonly ActivityId $activityId,
        private readonly SerializableDateTime $startDateTime,
        private readonly SportType $sportType,
        private array $data,
        private readonly ?Location $location = null,
        private readonly array $weather = [],
        private readonly ?GearId $gearId = null,
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
        return Latitude::fromOptionalString($this->data['start_latlng'][0] ?? null);
    }

    public function getLongitude(): ?Longitude
    {
        return Longitude::fromOptionalString($this->data['start_latlng'][1] ?? null);
    }

    public function getKudoCount(): int
    {
        return $this->data['kudos_count'] ?? 0;
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
        $hour = $this->getStartDate()->getHourWithoutLeadingZero();
        if (!empty($this->weather['hourly']['time'][$hour])) {
            // Use weather known for the given hour.
            $weather = [];
            foreach ($this->weather['hourly'] as $metric => $values) {
                $weather[$metric] = $values[$hour];
            }

            return Weather::fromMap($weather);
        }

        if (!empty($this->weather['daily'])) {
            // Use weather known for that day.
            $weather = [];
            foreach ($this->weather['daily'] as $metric => $values) {
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
        return $this->data['localImagePaths'] ?? [];
    }

    public function getTotalImageCount(): int
    {
        return $this->data['total_photo_count'] ?? 0;
    }

    public function getName(): string
    {
        return trim(str_replace('Zwift - ', '', $this->data['name']));
    }

    public function getDescription(): string
    {
        return trim($this->data['description'] ?? '');
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->data['distance'] / 1000);
    }

    public function getElevation(): Meter
    {
        return Meter::from($this->data['total_elevation_gain']);
    }

    public function getCalories(): int
    {
        return (int) ($this->data['calories'] ?? 0);
    }

    public function getAveragePower(): ?int
    {
        if (isset($this->data['average_watts'])) {
            return (int) round($this->data['average_watts']);
        }

        return null;
    }

    public function getMaxPower(): ?int
    {
        if (isset($this->data['max_watts'])) {
            return (int) round($this->data['max_watts']);
        }

        return null;
    }

    public function getAverageSpeed(): KmPerHour
    {
        return KmPerHour::from($this->data['average_speed'] * 3.6);
    }

    public function getMaxSpeed(): KmPerHour
    {
        return KmPerHour::from($this->data['max_speed'] * 3.6);
    }

    public function getAverageHeartRate(): ?int
    {
        if (isset($this->data['average_heartrate'])) {
            return (int) round($this->data['average_heartrate']);
        }

        return null;
    }

    public function getMaxHeartRate(): ?int
    {
        if (isset($this->data['max_heartrate'])) {
            return (int) round($this->data['max_heartrate']);
        }

        return null;
    }

    public function getAverageCadence(): ?int
    {
        return !empty($this->data['average_cadence']) ? (int) round($this->data['average_cadence']) : null;
    }

    public function getMaxCadence(): ?int
    {
        return $this->data['max_cadence'] ?? null;
    }

    public function enrichWithMaxCadence(int $maxCadence): void
    {
        $this->data['max_cadence'] = $maxCadence;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->data['moving_time'];
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->formatDurationForHumans($this->getMovingTimeInSeconds());
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/activities/'.$this->data['id'];
    }

    public function getPolylineSummary(): ?string
    {
        return $this->data['map']['summary_polyline'] ?? null;
    }

    public function getDeviceName(): ?string
    {
        if (!isset($this->data['device_name'])) {
            return null;
        }

        return $this->data['device_name'];
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
        return $this->data['segment_efforts'] ?? [];
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
