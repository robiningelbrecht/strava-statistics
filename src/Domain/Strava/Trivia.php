<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Time\Dates;

final readonly class Trivia
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function fromActivities(Activities $activities): self
    {
        return new self($activities);
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

    public function getLongestActivity(): Activity
    {
        /** @var Activity $longestActivity */
        $longestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getDistance()->toFloat() < $longestActivity->getDistance()->toFloat()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
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

    public function getFastestActivity(): Activity
    {
        /** @var Activity $fastestActivity */
        $fastestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getAverageSpeed()->toFloat() < $fastestActivity->getAverageSpeed()->toFloat()) {
                continue;
            }
            $fastestActivity = $activity;
        }

        return $fastestActivity;
    }

    public function getMostConsecutiveDaysOfCycling(): Dates
    {
        return Dates::fromDates($this->activities->map(
            fn (Activity $activity) => $activity->getStartDate(),
        ))->getLongestConsecutiveDateRange();
    }
}
