<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Time\Years;
use Carbon\CarbonInterval;

final readonly class YearlyStatistics
{
    private function __construct(
        private Activities $activities,
        private Years $years,
    ) {
    }

    public static function create(
        Activities $activities,
        Years $years,
    ): self {
        return new self($activities, $years);
    }

    /**
     * @return array<int, mixed>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->years as $year) {
            $statistics[(string) $year] = [
                'year' => $year,
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'totalCalories' => 0,
                'movingTimeInSeconds' => 0,
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $year = $activity->getStartDate()->getYear();

            ++$statistics[$year]['numberOfRides'];
            $statistics[$year]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$year]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$year]['movingTimeInSeconds'] += $activity->getMovingTimeInSeconds();
            $statistics[$year]['totalCalories'] += $activity->getCalories();
        }

        // @phpstan-ignore-next-line
        $statistics = array_values($statistics);
        foreach ($statistics as $key => &$statistic) {
            $statistic['movingTime'] = CarbonInterval::seconds($statistic['movingTimeInSeconds'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistic['differenceInDistanceYearBefore'] = null;
            if (isset($statistics[$key + 1]['totalDistance'])) {
                $statistic['differenceInDistanceYearBefore'] = Kilometer::from($statistic['totalDistance'] - $statistics[$key + 1]['totalDistance']);
            }

            $statistics[$key]['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$key]['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }
}
