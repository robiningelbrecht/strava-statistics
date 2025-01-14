<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Activity\WriteModel\Activities;
use App\Domain\Strava\Activity\WriteModel\Activity;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DistanceOverTimePerGearChartBuilder
{
    private function __construct(
        private Gears $gears,
        private Activities $activities,
        private UnitSystem $unitSystem,
        private SerializableDateTime $now,
    ) {
    }

    public static function fromGearAndActivities(
        Gears $gearCollection,
        Activities $activityCollection,
        UnitSystem $unitSystem,
        SerializableDateTime $now,
    ): self {
        return new self(
            gears: $gearCollection,
            activities: $activityCollection,
            unitSystem: $unitSystem,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $distanceOverTimePerGear = [];
        $gears = $this->gears->sortByIsRetired();

        $period = new \DatePeriod(
            start: $this->activities->getFirstActivityStartDate(),
            interval: new \DateInterval('P1D'),
            end: $this->now
        );

        foreach ($gears as $gear) {
            $runningTotal = 0;
            foreach ($period as $date) {
                $date = SerializableDateTime::fromDateTimeImmutable($date);
                $activitiesOnThisDay = $this->activities->filterOnDate($date)->filter(fn (Activity $activity) => $activity->getGearId() == $gear->getId());

                $runningTotal += $activitiesOnThisDay->sum(
                    fn (Activity $activity) => $activity->getDistance()->toUnitSystem($this->unitSystem)->toFloat()
                );
                $distanceOverTimePerGear[(string) $gear->getId()][] = [$date->format('Y-m-d'), round($runningTotal)];
            }
        }

        $series = [];
        $selectedSeries = [];
        /** @var Gear $gear */
        foreach ($gears as $gear) {
            $selectedSeries[$gear->getName()] = !$gear->isRetired();

            $series[] = [
                'name' => $gear->getName(),
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => $distanceOverTimePerGear[(string) $gear->getId()],
            ];
        }

        return [
            'backgroundColor' => '#ffffff',
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'legend' => [
                'selected' => $selectedSeries,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '{d} {MMM}',
                            'hour' => '',
                            'minute' => '',
                            'second' => '',
                            'millisecond' => '',
                            'none' => '',
                        ],
                    ],
                    'axisTick' => [
                        'show' => false,
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'Distance in '.Kilometer::zero()->toUnitSystem($this->unitSystem)->getSymbol(),
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                ],
            ],
            'series' => $series,
        ];
    }
}
