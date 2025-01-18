<?php

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Velocity\KmPerHour;
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
            $activitiesWithGear = $this->activities->filter(fn (Activity $activity) => $activity->getGearId() == $bike->getId());
            $countActivitiesWithGear = count($activitiesWithGear);
            $movingTimeInSeconds = $activitiesWithGear->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

            return [
                'name' => $bike->getName(),
                'distance' => $bike->getDistance(),
                'numberOfWorkouts' => $countActivitiesWithGear,
                'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'elevation' => Meter::from($activitiesWithGear->sum(fn (Activity $activity) => $activity->getElevation()->toFloat())),
                'averageDistance' => $countActivitiesWithGear > 0 ? Kilometer::from($bike->getDistance()->toFloat() / $countActivitiesWithGear) : Kilometer::zero(),
                'averageSpeed' => $movingTimeInSeconds > 0 ? Kilometer::from(($bike->getDistance()->toFloat() / $movingTimeInSeconds) * 3600) : Kilometer::zero(),
                'totalCalories' => $activitiesWithGear->sum(fn (Activity $activity) => $activity->getCalories()),
            ];
        });

        $activitiesWithOtherGear = $this->activities->filter(fn (Activity $activity) => empty($activity->getGearId()));
        $countActivitiesWithOtherGear = count($activitiesWithOtherGear);
        if (0 === $countActivitiesWithOtherGear) {
            return $statistics;
        }
        $distanceWithOtherGear = Kilometer::from($activitiesWithOtherGear->sum(fn (Activity $activity) => $activity->getDistance()->toFloat()));
        $movingTimeInSeconds = $activitiesWithOtherGear->sum(fn (Activity $activity) => $activity->getMovingTimeInSeconds());

        $statistics[] = [
            'name' => 'Other',
            'distance' => $distanceWithOtherGear,
            'numberOfWorkouts' => $countActivitiesWithOtherGear,
            'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'elevation' => Meter::from($activitiesWithOtherGear->sum(fn (Activity $activity) => $activity->getElevation()->toFloat())),
            'averageDistance' => Kilometer::from($distanceWithOtherGear->toFloat() / $countActivitiesWithOtherGear),
            'averageSpeed' => KmPerHour::from(($distanceWithOtherGear->toFloat() / $movingTimeInSeconds) * 3600),
            'totalCalories' => $activitiesWithOtherGear->sum(fn (Activity $activity) => $activity->getCalories()),
        ];

        return $statistics;
    }
}
