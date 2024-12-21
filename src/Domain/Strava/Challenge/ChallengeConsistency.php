<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Calendar\Week;
use App\Domain\Strava\MonthlyStatistics;

final readonly class ChallengeConsistency
{
    private Months $months;

    private function __construct(
        Months $months,
        private MonthlyStatistics $monthlyStatistics,
        private Activities $activities,
    ) {
        $this->months = $months->reverse();
    }

    public static function create(
        Months $months,
        MonthlyStatistics $monthlyStatistics,
        Activities $activities,
    ): self {
        return new self(
            months: $months,
            monthlyStatistics: $monthlyStatistics,
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
            if (!$monthlyStats = $this->monthlyStatistics->getStatisticsForMonth($month)) {
                foreach (ConsistencyChallenge::cases() as $consistencyChallenge) {
                    $consistency[$consistencyChallenge->value][] = 0;
                }
                continue;
            }

            $activities = $this->activities->filterOnMonth($month);

            $consistency[ConsistencyChallenge::KM_200->value][] = $monthlyStats['totalDistance'] >= 200;
            $consistency[ConsistencyChallenge::KM_600->value][] = $monthlyStats['totalDistance'] >= 600;
            $consistency[ConsistencyChallenge::KM_1250->value][] = $monthlyStats['totalDistance'] >= 1250;
            $consistency[ConsistencyChallenge::CLIMBING_7500->value][] = $monthlyStats['totalElevation'] >= 7500;
            $consistency[ConsistencyChallenge::GRAN_FONDO->value][] = $activities->max(
                fn (Activity $activity) => $activity->getDistance()->toFloat(),
            ) >= 100;

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
