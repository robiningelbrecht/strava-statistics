<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Week;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;

/**
 * @extends Collection<\App\Domain\Strava\Activity\ReadModel\ActivityDetails>
 */
final class Activities extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityDetails::class;
    }

    public function getFirstActivityStartDate(): SerializableDateTime
    {
        $startDate = null;
        foreach ($this as $activity) {
            if ($startDate && $activity->getStartDate()->isAfterOrOn($startDate)) {
                continue;
            }
            $startDate = $activity->getStartDate();
        }

        if (!$startDate) {
            throw new \RuntimeException('No activities found');
        }

        return $startDate;
    }

    public function filterOnDate(SerializableDateTime $date): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $activity->getStartDate()->format('Ymd') === $date->format('Ymd'));
    }

    public function filterOnMonth(Month $month): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $activity->getStartDate()->format(Month::MONTH_ID_FORMAT) === $month->getId());
    }

    public function filterOnWeek(Week $week): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $activity->getStartDate()->getYearAndWeekNumberString() === $week->getId());
    }

    public function filterOnDateRange(SerializableDateTime $fromDate, SerializableDateTime $toDate): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $activity->getStartDate()->isAfterOrOn($fromDate) && $activity->getStartDate()->isBeforeOrOn($toDate));
    }

    public function filterOnActivityType(ActivityType $activityType): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $activityType === $activity->getSportType()->getActivityType());
    }

    public function filterOnSportType(SportType $sportType): Activities
    {
        return $this->filter(fn (ActivityDetails $activity) => $sportType === $activity->getSportType());
    }

    public function getByActivityId(ActivityId $activityId): ActivityDetails
    {
        $activities = $this->filter(fn (ActivityDetails $activity) => $activityId == $activity->getId())->toArray();

        /** @var ActivityDetails $activity */
        $activity = reset($activities);

        return $activity;
    }

    public function getUniqueYears(): Years
    {
        $years = Years::empty();
        /** @var ActivityDetails $activity */
        foreach ($this as $activity) {
            $activityYear = Year::fromInt($activity->getStartDate()->getYear());
            if ($years->has($activityYear)) {
                continue;
            }
            $years->add($activityYear);
        }

        return $years;
    }
}
