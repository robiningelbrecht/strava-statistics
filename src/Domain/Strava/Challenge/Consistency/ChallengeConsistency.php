<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ReadModel\Activities;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Calendar\Week;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;

final readonly class ChallengeConsistency
{
    private Months $months;

    private function __construct(
        Months $months,
        private Activities $activities,
    ) {
        $this->months = $months->reverse();
    }

    public static function create(
        Months $months,
        Activities $activities,
    ): self {
        return new self(
            months: $months,
            activities: $activities
        );
    }

    public function getMonths(): Months
    {
        return $this->months;
    }

    /**
     * @return array<mixed>
     */
    public function getConsistencies(): array
    {
        $consistency = [];

        /** @var \App\Domain\Strava\Calendar\Month $month */
        foreach ($this->months as $month) {
            $activities = $this->activities->filterOnMonth($month);
            if ($activities->isEmpty()) {
                foreach (ConsistencyChallenge::cases() as $consistencyChallenge) {
                    $consistency[$consistencyChallenge->value][] = 0;
                }
                continue;
            }

            $bikeActivities = $activities->filterOnActivityType(ActivityType::RIDE);
            $bikeTotalDistance = Kilometer::from($bikeActivities->sum(fn (ActivityDetails $activity) => $activity->getDistance()->toFloat()));
            $bikeTotalElevation = Meter::from($bikeActivities->sum(fn (ActivityDetails $activity) => $activity->getElevation()->toFloat()));

            $runActivities = $activities->filterOnActivityType(ActivityType::RUN);
            $maxDistanceRunningActivity = !$runActivities->isEmpty() ? Kilometer::from($runActivities->max(fn (ActivityDetails $activity) => $activity->getDistance()->toFloat())) : Kilometer::zero();
            $runTotalDistance = Kilometer::from($runActivities->sum(fn (ActivityDetails $activity) => $activity->getDistance()->toFloat()));
            $runTotalElevation = Meter::from($runActivities->sum(fn (ActivityDetails $activity) => $activity->getElevation()->toFloat()));

            $consistency[ConsistencyChallenge::RIDE_KM_200->value][] = $bikeTotalDistance->toFloat() >= 200;
            $consistency[ConsistencyChallenge::RIDE_KM_600->value][] = $bikeTotalDistance->toFloat() >= 600;
            $consistency[ConsistencyChallenge::RIDE_KM_1250->value][] = $bikeTotalDistance->toFloat() >= 1250;
            $consistency[ConsistencyChallenge::RIDE_CLIMBING_7500->value][] = $bikeTotalElevation->toFloat() >= 7500;
            $consistency[ConsistencyChallenge::RIDE_GRAN_FONDO->value][] = !$bikeActivities->isEmpty() && $bikeActivities->max(
                fn (ActivityDetails $activity) => $activity->getDistance()->toFloat(),
            ) >= 100;
            $consistency[ConsistencyChallenge::RUN_KM_5->value][] = $maxDistanceRunningActivity->toFloat() >= 5;
            $consistency[ConsistencyChallenge::RUN_KM_10->value][] = $maxDistanceRunningActivity->toFloat() >= 10;
            $consistency[ConsistencyChallenge::RUN_HALF_MARATHON->value][] = $maxDistanceRunningActivity->toFloat() >= 21.1;
            $consistency[ConsistencyChallenge::RUN_KM_100_TOTAL->value][] = $runTotalDistance->toFloat() >= 100;
            $consistency[ConsistencyChallenge::RUN_CLIMBING_2000->value][] = $runTotalElevation->toFloat() >= 2000;

            // First monday of the month until 4 weeks later, sunday.
            $firstMonday = $month->getFirstMonday();
            $week = Week::fromYearAndWeekNumber(
                year: $firstMonday->getYear(),
                weekNumber: $firstMonday->getWeekNumber()
            );
            $hasTwoDaysOfActivity = true;
            for ($i = 0; $i < 4; ++$i) {
                $numberOfActivities = count($this->activities->filterOnWeek($week));
                if ($numberOfActivities < 2) {
                    $hasTwoDaysOfActivity = false;
                    break;
                }
                $week = $week->getNextWeek();
            }

            $consistency[ConsistencyChallenge::TWO_DAYS_OF_ACTIVITY_4_WEEKS->value][] = $hasTwoDaysOfActivity;
        }

        // Filter out challenges that have never been completed.
        foreach ($consistency as $challenge => $achievements) {
            if (!empty(array_filter($achievements))) {
                continue;
            }
            unset($consistency[$challenge]);
        }

        return $consistency;
    }
}
