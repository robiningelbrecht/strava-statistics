<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ReadModel\Activities;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Domain\Strava\Activity\WriteModel\Activity;
use App\Infrastructure\ValueObject\Time\Dates;

final readonly class Trivia
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function create(Activities $activities): self
    {
        return new self($activities);
    }

    public function getTotalKudosReceived(): int
    {
        return (int) $this->activities->sum(fn (ActivityDetails $activity) => $activity->getKudoCount());
    }

    public function getMostKudotedActivity(): ActivityDetails
    {
        /** @var ActivityDetails $mostKudotedActivity */
        $mostKudotedActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getKudoCount() < $mostKudotedActivity->getKudoCount()) {
                continue;
            }
            $mostKudotedActivity = $activity;
        }

        return $mostKudotedActivity;
    }

    public function getFirstActivity(): ActivityDetails
    {
        /** @var ActivityDetails $fistActivity */
        $fistActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate() > $fistActivity->getStartDate()) {
                continue;
            }
            $fistActivity = $activity;
        }

        return $fistActivity;
    }

    public function getEarliestActivity(): ActivityDetails
    {
        /** @var ActivityDetails $earliestActivity */
        $earliestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() > $earliestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $earliestActivity = $activity;
        }

        return $earliestActivity;
    }

    public function getLatestActivity(): ActivityDetails
    {
        /** @var ActivityDetails $latestActivity */
        $latestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() < $latestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $latestActivity = $activity;
        }

        return $latestActivity;
    }

    public function getLongestRide(): ?ActivityDetails
    {
        $bikeActivities = $this->activities->filterOnActivityType(ActivityType::RIDE);

        if (!$longestActivity = $bikeActivities->getFirst()) {
            return null;
        }
        /** @var ActivityDetails $activity */
        foreach ($bikeActivities as $activity) {
            if ($activity->getDistance()->toFloat() < $longestActivity->getDistance()->toFloat()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
    }

    public function getFastestRide(): ?ActivityDetails
    {
        $bikeActivities = $this->activities->filterOnActivityType(ActivityType::RIDE);

        if (!$fastestActivity = $bikeActivities->getFirst()) {
            return null;
        }

        /** @var ActivityDetails $activity */
        foreach ($bikeActivities as $activity) {
            if ($activity->getAverageSpeed()->toFloat() < $fastestActivity->getAverageSpeed()->toFloat()) {
                continue;
            }
            $fastestActivity = $activity;
        }

        return $fastestActivity;
    }

    public function getActivityWithHighestElevation(): ActivityDetails
    {
        /** @var ActivityDetails $mostElevationActivity */
        $mostElevationActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getElevation()->toFloat() < $mostElevationActivity->getElevation()->toFloat()) {
                continue;
            }
            $mostElevationActivity = $activity;
        }

        return $mostElevationActivity;
    }

    public function getMostConsecutiveDaysOfCycling(): Dates
    {
        return Dates::fromDates($this->activities->map(
            fn (Activity $activity) => $activity->getStartDate(),
        ))->getLongestConsecutiveDateRange();
    }
}
