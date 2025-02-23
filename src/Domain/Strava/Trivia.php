<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\Dates;

final class Trivia
{
    public static ?Trivia $instance = null;

    private function __construct(
        private readonly Activities $activities,
    ) {
    }

    public static function getInstance(Activities $activities): self
    {
        if (null === self::$instance) {
            self::$instance = new self($activities);
        }

        return self::$instance;
    }

    public function getTotalKudosReceived(): int
    {
        return (int) $this->activities->sum(fn (Activity $activity) => $activity->getKudoCount());
    }

    public function getMostKudotedActivity(): Activity
    {
        /** @var Activity $mostKudotedActivity */
        $mostKudotedActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getKudoCount() < $mostKudotedActivity->getKudoCount()) {
                continue;
            }
            $mostKudotedActivity = $activity;
        }

        return $mostKudotedActivity;
    }

    public function getFirstActivity(): Activity
    {
        /** @var Activity $fistActivity */
        $fistActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate() > $fistActivity->getStartDate()) {
                continue;
            }
            $fistActivity = $activity;
        }

        return $fistActivity;
    }

    public function getEarliestActivity(): Activity
    {
        /** @var Activity $earliestActivity */
        $earliestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() > $earliestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $earliestActivity = $activity;
        }

        return $earliestActivity;
    }

    public function getLatestActivity(): Activity
    {
        /** @var Activity $latestActivity */
        $latestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() < $latestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $latestActivity = $activity;
        }

        return $latestActivity;
    }

    public function getLongestWorkout(): Activity
    {
        /** @var Activity $longestActivity */
        $longestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getMovingTimeInSeconds() < $longestActivity->getMovingTimeInSeconds()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
    }

    public function getFastestRide(): ?Activity
    {
        $bikeActivities = $this->activities->filterOnActivityType(ActivityType::RIDE);

        if (!$fastestActivity = $bikeActivities->getFirst()) {
            return null;
        }

        /** @var Activity $activity */
        foreach ($bikeActivities as $activity) {
            if ($activity->getAverageSpeed()->toFloat() < $fastestActivity->getAverageSpeed()->toFloat()) {
                continue;
            }
            $fastestActivity = $activity;
        }

        return $fastestActivity;
    }

    public function getActivityWithHighestElevation(): Activity
    {
        /** @var Activity $mostElevationActivity */
        $mostElevationActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getElevation()->toFloat() < $mostElevationActivity->getElevation()->toFloat()) {
                continue;
            }
            $mostElevationActivity = $activity;
        }

        return $mostElevationActivity;
    }

    public function getMostConsecutiveDaysOfWorkingOut(): Dates
    {
        return Dates::fromDates($this->activities->map(
            fn (Activity $activity) => $activity->getStartDate(),
        ))->getLongestConsecutiveDateRange();
    }
}
