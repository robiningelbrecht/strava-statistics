<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'ActivitySplit_activityIdUnitSystemIndex', columns: ['activityId', 'unitSystem'])]
final readonly class ActivitySplit
{
    use ProvideTimeFormats;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private UnitSystem $unitSystem,
        #[ORM\Id, ORM\Column(type: 'integer')]
        private int $splitNumber,
        #[ORM\Column(type: 'integer')]
        private Meter $distance,
        #[ORM\Column(type: 'integer')]
        private int $elapsedTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private int $movingTimeInSeconds,
        #[ORM\Column(type: 'integer')]
        private Meter $elevationDifference,
        #[ORM\Column(type: 'float')]
        private MetersPerSecond $averageSpeed,
        #[ORM\Column(type: 'float')]
        private MetersPerSecond $minAverageSpeed,
        #[ORM\Column(type: 'integer')]
        private MetersPerSecond $maxAverageSpeed,
        #[ORM\Column(type: 'integer')]
        private int $paceZone,
    ) {
    }

    public static function create(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        int $splitNumber,
        Meter $distance,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $elevationDifference,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $minAverageSpeed,
        MetersPerSecond $maxAverageSpeed,
        int $paceZone,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            splitNumber: $splitNumber,
            distance: $distance,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            elevationDifference: $elevationDifference,
            averageSpeed: $averageSpeed,
            minAverageSpeed: $minAverageSpeed,
            maxAverageSpeed: $maxAverageSpeed,
            paceZone: $paceZone,
        );
    }

    public static function fromState(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        int $splitNumber,
        Meter $distance,
        int $elapsedTimeInSeconds,
        int $movingTimeInSeconds,
        Meter $elevationDifference,
        MetersPerSecond $averageSpeed,
        MetersPerSecond $minAverageSpeed,
        MetersPerSecond $maxAverageSpeed,
        int $paceZone,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            splitNumber: $splitNumber,
            distance: $distance,
            elapsedTimeInSeconds: $elapsedTimeInSeconds,
            movingTimeInSeconds: $movingTimeInSeconds,
            elevationDifference: $elevationDifference,
            averageSpeed: $averageSpeed,
            minAverageSpeed: $minAverageSpeed,
            maxAverageSpeed: $maxAverageSpeed,
            paceZone: $paceZone,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getUnitSystem(): UnitSystem
    {
        return $this->unitSystem;
    }

    public function getSplitNumber(): int
    {
        return $this->splitNumber;
    }

    public function getDistance(): Meter
    {
        return $this->distance;
    }

    public function getElapsedTimeInSeconds(): int
    {
        return $this->elapsedTimeInSeconds;
    }

    public function getMovingTimeInSeconds(): int
    {
        return $this->movingTimeInSeconds;
    }

    public function getElevationDifference(): Meter
    {
        return $this->elevationDifference;
    }

    public function getAverageSpeed(): MetersPerSecond
    {
        return $this->averageSpeed;
    }

    public function getRelativePacePercentage(): float
    {
        if (0.0 === $this->getMaxAverageSpeed()->toFloat()) {
            return 0;
        }
        if (0.0 === $this->getMaxAverageSpeed()->toFloat() - $this->getMinAverageSpeed()->toFloat()) {
            return 0;
        }

        $adjustMinSpeedPercentage = 0.8;
        $adjustMaxSpeedPercentage = 1.1;

        $maxAverageSpeed = MetersPerSecond::from($this->getMinAverageSpeed()->toFloat() * $adjustMaxSpeedPercentage);
        $minAverageSpeed = MetersPerSecond::from($this->getMaxAverageSpeed()->toFloat() * $adjustMinSpeedPercentage);

        $step = round(100 / ($maxAverageSpeed->toFloat() - $minAverageSpeed->toFloat()), 2);
        // Relative = step *  (value - min).
        $relativePercentage = $step * ($this->getAverageSpeed()->toFloat() - $minAverageSpeed->toFloat());

        return round($relativePercentage, 2);
    }

    public function getMinAverageSpeed(): MetersPerSecond
    {
        return $this->minAverageSpeed;
    }

    public function getMaxAverageSpeed(): MetersPerSecond
    {
        return $this->maxAverageSpeed;
    }

    public function getPaceFormatted(): string
    {
        $pace = $this->getAverageSpeed()->toSecPerKm();

        return $this->formatDurationForHumans($pace->toInt());
    }

    public function getPaceZone(): int
    {
        return $this->paceZone;
    }
}
