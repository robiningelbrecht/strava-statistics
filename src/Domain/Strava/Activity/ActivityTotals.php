<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final readonly class ActivityTotals
{
    private function __construct(
        private Activities $activities,
        private SerializableDateTime $now,
    ) {
    }

    public function getDistance(): float
    {
        return $this->activities->sum(fn (Activity $activity) => $activity->getDistance()->toFloat());
    }

    public function getElevation(): float
    {
        return $this->activities->sum(fn (Activity $activity) => $activity->getElevation()->toFloat());
    }

    public function getCalories(): int
    {
        return (int) $this->activities->sum(fn (Activity $activity) => $activity->getCalories());
    }

    public function getMovingTimeFormatted(): string
    {
        $seconds = $this->activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->activities->getFirstActivityStartDate();
    }

    public function getDailyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / $diff->days;
    }

    public function getWeeklyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / ceil($diff->days / 7);
    }

    public function getMonthlyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / (($diff->y * 12) + $diff->m + 1);
    }

    public function getTotalDaysSinceFirstActivity(): string
    {
        $days = (int) $this->now->diff($this->getStartDate())->days;

        return CarbonInterval::days($days)->cascade()->forHumans(['minimumUnit' => 'day', 'join' => [' ', ' and '], 'parts' => 2]);
    }

    public function getTotalDaysOfCycling(): int
    {
        return count(array_unique($this->activities->map(fn (Activity $activity) => $activity->getStartDate()->format('Ymd'))));
    }

    public static function fromActivities(Activities $activities, SerializableDateTime $now): self
    {
        return new self($activities, $now);
    }
}
