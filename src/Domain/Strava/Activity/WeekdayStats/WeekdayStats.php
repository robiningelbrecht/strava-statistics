<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\WeekdayStats;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Strava\Activity\WriteModel\Activities;
use App\Domain\Strava\Activity\WriteModel\Activity;
use Carbon\CarbonInterval;

final readonly class WeekdayStats
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function fromActivities(
        Activities $activities,
    ): self {
        return new self(
            $activities,
        );
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $statistics = [];
        $daysOfTheWeekMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $totalMovingTime = $this->activities->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekDay) {
            $statistics[$daysOfTheWeekMap[$weekDay]] = [
                'numberOfWorkouts' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
                'averageDistance' => 0,
            ];
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $weekDay = $daysOfTheWeekMap[$activity->getStartDate()->format('w')];

            ++$statistics[$weekDay]['numberOfWorkouts'];

            $statistics[$weekDay]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$weekDay]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$weekDay]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$weekDay]['averageDistance'] = $statistics[$weekDay]['totalDistance'] / $statistics[$weekDay]['numberOfWorkouts'];
            $statistics[$weekDay]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$weekDay]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$weekDay]['percentage'] = round($statistics[$weekDay]['movingTime'] / $totalMovingTime * 100, 2);
        }

        foreach ($statistics as $weekDay => $statistic) {
            $statistics[$weekDay]['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$weekDay]['averageDistance'] = Kilometer::from($statistic['averageDistance']);
            $statistics[$weekDay]['totalElevation'] = Meter::from($statistic['totalElevation']);
        }

        return $statistics;
    }
}
