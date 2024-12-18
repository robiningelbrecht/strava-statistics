<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Nominatim\Location;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Ftp\FtpValue;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\LeafletMap;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Eventing\RecordsEvents;
use App\Infrastructure\Time\TimeFormatter;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Activity
{
    use TimeFormatter;
    use RecordsEvents;

    public const string DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';
    private ?string $gearName = null;
    /** @var array<mixed> */
    private array $bestPowerOutputs = [];
    private ?FtpValue $ftp = null;
    private ?SerializableDateTime $athleteBirthday = null;
    private bool $hasDetailedPowerData = false;

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
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
        array $data,
        ?GearId $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
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
        array $data,
        ?Location $location = null,
        array $weather = [],
        ?GearId $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
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

    public function getType(): ActivityType
    {
        return ActivityType::from($this->data['type']);
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

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function enrichWithGearName(string $gearName): void
    {
        $this->gearName = $gearName;
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
        $this->hasDetailedPowerData = !empty($bestPowerOutputs);
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

    public function getDescription(): string
    {
        return trim($this->data['description'] ?? '');
    }

    public function updateDescription(string $description): self
    {
        $this->data['description'] = $description;

        return $this;
    }

    public function getDistanceInKilometer(): float
    {
        return $this->data['distance'] / 1000;
    }

    public function getElevationInMeter(): float
    {
        return $this->data['total_elevation_gain'];
    }

    public function updateElevation(float $elevationInLMeter): self
    {
        $this->data['total_elevation_gain'] = $elevationInLMeter;

        return $this;
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

    public function getAverageSpeedInKmPerH(): float
    {
        return round($this->data['average_speed'] * 3.6, 1);
    }

    public function getMaxSpeedInKmPerH(): float
    {
        return round($this->data['max_speed'] * 3.6, 1);
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

    public function getIntensity(): ?int
    {
        // To calculate intensity, we need
        // 1) Max and average heart rate
        // OR
        // 2) FTP and average power
        if (($ftp = $this->getFtp()) && ($averagePower = $this->getAveragePower()) && $this->hasDetailedPowerData()) {
            // Use more complicated and more accurate calculation.
            // intensityFactor = averagePower / FTP
            // (durationInSeconds * averagePower * intensityFactor) / (FTP x 3600) * 100
            return (int) round(($this->getMovingTimeInSeconds() * $averagePower * ($averagePower / $ftp->getValue())) / ($ftp->getValue() * 3600) * 100);
        }

        if (($averageHeartRate = $this->getAverageHeartRate()) && ($athleteMaxHeartRate = $this->getAthleteMaxHeartRate())) {
            // Use simplified, less accurate calculation.
            // maxHeartRate = = (220 - age) x 0.92
            // intensityFactor = averageHeartRate / maxHeartRate
            // (durationInSeconds x averageHeartRate x intensityFactor) / (maxHeartRate x 3600) x 100
            $maxHeartRate = round($athleteMaxHeartRate * 0.92);

            return (int) round(($this->getMovingTimeInSeconds() * $averageHeartRate * ($averageHeartRate / $maxHeartRate)) / ($maxHeartRate * 3600) * 100);
        }

        return null;
    }

    public function getFtp(): ?FtpValue
    {
        return $this->ftp;
    }

    public function enrichWithFtp(FtpValue $ftp): void
    {
        $this->ftp = $ftp;
    }

    public function getAthleteAgeInYears(): ?int
    {
        return $this->athleteBirthday?->diff($this->getStartDate())->y;
    }

    public function getAthleteMaxHeartRate(): ?int
    {
        if (!$age = $this->getAthleteAgeInYears()) {
            return null;
        }

        return 220 - $age;
    }

    public function enrichWithAthleteBirthday(SerializableDateTime $birthday): void
    {
        $this->athleteBirthday = $birthday;
    }

    public function hasDetailedPowerData(): bool
    {
        return $this->hasDetailedPowerData;
    }

    public function updateHasDetailedPowerData(bool $flag): void
    {
        $this->hasDetailedPowerData = $flag;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
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
        if (ActivityType::RIDE === $this->getType()) {
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
     * @return string[]
     */
    public function getSearchables(): array
    {
        return [$this->getName()];
    }

    public function delete(): void
    {
        $this->recordThat(new ActivityWasDeleted($this->getId()));
    }

    /**
     * @return array<mixed>
     */
    public function getSegmentEfforts(): array
    {
        return $this->data['segment_efforts'] ?? [];
    }

    public function removeSegments(): void
    {
        if (isset($this->data['segment_efforts'])) {
            unset($this->data['segment_efforts']);
        }
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
