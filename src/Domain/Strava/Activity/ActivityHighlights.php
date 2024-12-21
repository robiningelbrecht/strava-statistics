<?php

namespace App\Domain\Strava\Activity;

final readonly class ActivityHighlights
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public function getLongestDistance(): float
    {
        return $this->activities->max(fn (Activity $activity) => $activity->getDistance()->toFloat());
    }

    public function getHighestElevation(): float
    {
        return $this->activities->max(fn (Activity $activity) => $activity->getElevation()->toFloat());
    }

    public function getHighestAveragePower(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getAveragePower() ?? 0);
    }

    public function getFastestAverageSpeed(): float
    {
        return $this->activities->max(fn (Activity $activity) => $activity->getAverageSpeed()->toFloat());
    }

    public function getHighestAverageHeartRate(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getAverageHeartRate() ?? 0);
    }

    public function getCalories(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getCalories());
    }

    public function getLongestMovingTimeFormatted(): ?string
    {
        $activityWithMaxMovingTime = null;
        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            if ($activity->getMovingTimeInSeconds() < $activityWithMaxMovingTime?->getMovingTimeInSeconds()) {
                continue;
            }
            $activityWithMaxMovingTime = $activity;
        }

        if (!$activityWithMaxMovingTime) {
            return null;
        }

        return $activityWithMaxMovingTime->getMovingTimeFormatted();
    }

    public static function fromActivities(Activities $activities): self
    {
        return new self($activities);
    }
}
