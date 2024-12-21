<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final readonly class DistanceBreakdown
{
    private function __construct(
        private Activities $activities,
    ) {
    }

    public static function fromActivities(Activities $activities): self
    {
        return new self($activities);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $numberOfBreakdowns = 11;
        $statistics = [];
        $longestDistanceForActivity = $this->activities->max(
            fn (Activity $activity) => $activity->getDistance()->toFloat()
        );

        $breakdownOnKm = ceil(($longestDistanceForActivity / $numberOfBreakdowns) / 5) * 5;

        $range = range($breakdownOnKm, ceil($longestDistanceForActivity / $breakdownOnKm) * $breakdownOnKm, $breakdownOnKm);
        foreach ($range as $breakdownLimit) {
            $statistics[$breakdownLimit] = [
                'label' => sprintf('%d - %d km', $breakdownLimit - $breakdownOnKm, $breakdownLimit),
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'averageDistance' => 0,
                'averageSpeed' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            /** @var Activity $activity */
            $distance = $activity->getDistance()->toFloat();
            if ($distance <= 0) {
                continue;
            }
            $distanceBreakdown = ceil($activity->getDistance()->toFloat() / $breakdownOnKm) * $breakdownOnKm;

            ++$statistics[$distanceBreakdown]['numberOfRides'];
            $statistics[$distanceBreakdown]['totalDistance'] += $activity->getDistance()->toFloat();
            $statistics[$distanceBreakdown]['totalElevation'] += $activity->getElevation()->toFloat();
            $statistics[$distanceBreakdown]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$distanceBreakdown]['averageDistance'] = $statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['numberOfRides'];
            if ($statistics[$distanceBreakdown]['movingTime'] > 0) {
                $statistics[$distanceBreakdown]['averageSpeed'] = ($statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['movingTime']) * 3600;
            }
            $statistics[$distanceBreakdown]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$distanceBreakdown]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        foreach ($statistics as $distanceBreakdown => $statistic) {
            $statistics[$distanceBreakdown]['totalDistance'] = Kilometer::from($statistic['totalDistance']);
            $statistics[$distanceBreakdown]['averageDistance'] = Kilometer::from($statistic['averageDistance']);
            $statistics[$distanceBreakdown]['totalElevation'] = Meter::from($statistic['totalElevation']);
            $statistics[$distanceBreakdown]['averageSpeed'] = KmPerHour::from($statistic['averageSpeed']);
        }

        return $statistics;
    }
}
