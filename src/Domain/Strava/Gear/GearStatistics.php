<?php

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final readonly class GearStatistics
{
    private function __construct(
        private Activities $activities,
        private Gears $bikes,
    ) {
    }

    public static function fromActivitiesAndGear(
        Activities $activities,
        Gears $bikes): self
    {
        return new self($activities, $bikes);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $statistics = $this->bikes->map(function (Gear $bike) {
            $activitiesWithBike = $this->activities->filter(fn (Activity $activity) => $activity->getGearId() == $bike->getId());
            $countActivitiesWithBike = count($activitiesWithBike);
            $movingTimeInSeconds = $activitiesWithBike->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

            return [
                'name' => $bike->getName(),
                'distance' => $bike->getDistanceInKm(),
                'numberOfRides' => $countActivitiesWithBike,
                'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'elevation' => $activitiesWithBike->sum(fn (Activity $activity) => $activity->getElevation()->toFloat()),
                'averageDistance' => $countActivitiesWithBike > 0 ? $bike->getDistanceInKm() / $countActivitiesWithBike : 0,
                'averageSpeed' => $movingTimeInSeconds > 0 ? ($bike->getDistanceInKm() / $movingTimeInSeconds) * 3600 : 0,
                'totalCalories' => $activitiesWithBike->sum(fn (Activity $activity) => $activity->getCalories()),
            ];
        });

        $activitiesWithOtherBike = $this->activities->filter(fn (Activity $activity) => empty($activity->getGearId()));
        $countActivitiesWithOtherBike = count($activitiesWithOtherBike);
        if (0 === $countActivitiesWithOtherBike) {
            return $statistics;
        }
        $distanceWithOtherBike = $activitiesWithOtherBike->sum(fn (Activity $activity) => $activity->getDistance()->toFloat());
        $movingTimeInSeconds = $activitiesWithOtherBike->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        $statistics[] = [
            'name' => 'Other',
            'distance' => $distanceWithOtherBike,
            'numberOfRides' => $countActivitiesWithOtherBike,
            'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'elevation' => $activitiesWithOtherBike->sum(fn (Activity $activity) => $activity->getElevation()->toFloat()),
            'averageDistance' => $distanceWithOtherBike / $countActivitiesWithOtherBike,
            'averageSpeed' => ($distanceWithOtherBike / $movingTimeInSeconds) * 3600,
            'totalCalories' => $activitiesWithOtherBike->sum(fn (Activity $activity) => $activity->getCalories()),
        ];

        return $statistics;
    }
}
