<?php

namespace App\Domain\Strava\Activity\Eddington;

use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Activity\WriteModel\Activities;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class Eddington
{
    private const string DATE_FORMAT = 'Y-m-d';
    /** @var array<string, int|float> */
    private array $distancesPerDay = [];
    private ?int $eddingtonNumber = null;

    private function __construct(
        private readonly Activities $activities,
        private readonly UnitSystem $unitSystem,
    ) {
    }

    /**
     * @return array<string, float|int>
     */
    private function getDistancesPerDay(): array
    {
        if (!empty($this->distancesPerDay)) {
            return $this->distancesPerDay;
        }

        $this->distancesPerDay = [];
        foreach ($this->activities as $activity) {
            $day = $activity->getStartDate()->format(self::DATE_FORMAT);
            if (!array_key_exists($day, $this->distancesPerDay)) {
                $this->distancesPerDay[$day] = 0;
            }

            $distance = $activity->getDistance()->toUnitSystem($this->unitSystem);
            $this->distancesPerDay[$day] += $distance->toFloat();
        }

        return $this->distancesPerDay;
    }

    public function getLongestDistanceInADay(): int
    {
        if (empty($this->getDistancesPerDay())) {
            return 0;
        }

        return (int) round(max($this->getDistancesPerDay()));
    }

    /**
     * @return array<int<1, max>, int<0, max>>
     */
    public function getTimesCompletedData(): array
    {
        $data = [];
        for ($distance = 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            // We need to count the number of days we exceeded this distance.
            $data[$distance] = count(array_filter($this->getDistancesPerDay(), fn (float $distanceForDay) => $distanceForDay >= $distance));
        }

        return $data;
    }

    public function getNumber(): int
    {
        if (!is_null($this->eddingtonNumber)) {
            return $this->eddingtonNumber;
        }

        $number = 0;
        for ($distance = 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            $timesCompleted = count(array_filter($this->getDistancesPerDay(), fn (float $distanceForDay) => $distanceForDay >= $distance));
            if ($timesCompleted < $distance) {
                break;
            }
            $number = $distance;
        }

        $this->eddingtonNumber = $number;

        return $this->eddingtonNumber;
    }

    /**
     * @return array<int, int>
     */
    public function getDaysToCompleteForFutureNumbers(): array
    {
        $futureNumbers = [];
        $eddingtonNumber = $this->getNumber();
        $timesCompleted = $this->getTimesCompletedData();
        for ($distance = $eddingtonNumber + 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            $futureNumbers[$distance] = $distance - $timesCompleted[$distance];
        }

        return $futureNumbers;
    }

    /**
     * @return array<int, SerializableDateTime>
     */
    public function getEddingtonHistory(): array
    {
        $history = [];
        $eddingtonNumber = $this->getNumber();
        // We need the distances sorted by oldest => newest.
        $distancesPerDay = array_reverse($this->getDistancesPerDay());

        for ($distance = $eddingtonNumber; $distance > 0; --$distance) {
            $countForDistance = 0;
            foreach ($distancesPerDay as $day => $distanceInDay) {
                if ($distanceInDay >= $distance) {
                    ++$countForDistance;
                }
                if ($countForDistance === $distance) {
                    // This is the day we reached the eddington Number.
                    $history[$distance] = SerializableDateTime::fromString($day);
                    break;
                }
            }
        }

        return array_reverse($history, true);
    }

    public static function fromActivities(
        Activities $activities,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            activities: $activities,
            unitSystem: $unitSystem
        );
    }
}
