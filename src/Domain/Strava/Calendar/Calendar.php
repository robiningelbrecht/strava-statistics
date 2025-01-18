<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Domain\Strava\Activity\Activities;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Calendar
{
    private function __construct(
        private Month $month,
        private Activities $activities,
    ) {
    }

    public static function create(
        Month $month,
        Activities $activities,
    ): self {
        return new self(
            month: $month,
            activities: $activities
        );
    }

    public function getMonth(): Month
    {
        return $this->month;
    }

    public function getDays(): Days
    {
        $previousMonth = $this->month->getPreviousMonth();
        $nextMonth = $this->month->getNextMonth();
        $numberOfDaysInPreviousMonth = $previousMonth->getNumberOfDays();

        $days = Days::empty();
        for ($i = 1; $i < $this->month->getWeekDayOfFirstDay(); ++$i) {
            // Prepend with days of previous month.
            $dayNumber = $numberOfDaysInPreviousMonth - ($this->month->getWeekDayOfFirstDay() - $i - 1);
            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->activities->filterOnDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$previousMonth->getMonth().'-'.$previousMonth->getYear(),
                ))
            ));
        }

        for ($i = 0; $i < $this->month->getNumberOfDays(); ++$i) {
            $dayNumber = $i + 1;
            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: true,
                activities: $this->activities->filterOnDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$this->month->getMonth().'-'.$this->month->getYear(),
                ))
            ));
        }

        for ($i = 0; $i < count($days) % 7; ++$i) {
            // Append with days of next month.
            $dayNumber = $i + 1;
            $days->add(Day::create(
                dayNumber: $dayNumber,
                isCurrentMonth: false,
                activities: $this->activities->filterOnDate(SerializableDateTime::createFromFormat(
                    format: 'd-n-Y',
                    datetime: $dayNumber.'-'.$nextMonth->getMonth().'-'.$nextMonth->getYear(),
                ))
            ));
        }

        return $days;
    }
}
