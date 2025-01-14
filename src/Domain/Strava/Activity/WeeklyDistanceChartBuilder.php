<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Activity\WriteModel\Activities;
use App\Domain\Strava\Activity\WriteModel\Activity;
use App\Domain\Strava\Calendar\Week;
use App\Domain\Strava\Calendar\Weeks;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class WeeklyDistanceChartBuilder
{
    private function __construct(
        private Activities $activities,
        private UnitSystem $unitSystem,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        Activities $activities,
        UnitSystem $unitSystem,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            unitSystem: $unitSystem,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $weeks = Weeks::create(
            startDate: $this->activities->getFirstActivityStartDate(),
            now: $this->now
        );
        $zoomValueSpan = 10;
        $data = $this->getData($weeks);
        if (empty(array_filter($data[0]))) {
            // Activities do not contain distances.
            return [];
        }

        $xAxisLabels = [];
        /** @var Week $week */
        foreach ($weeks as $week) {
            if ($week == $weeks->getFirst() || in_array($week->getLabel(), $xAxisLabels)) {
                $xAxisLabels[] = '';
                continue;
            }
            $xAxisLabels[] = $week->getLabel();
        }

        $serie = [
            'type' => 'line',
            'smooth' => false,
            'label' => [
                'show' => true,
                'rotate' => -45,
            ],
            'lineStyle' => [
                'width' => 1,
            ],
            'symbolSize' => 6,
            'showSymbol' => true,
            'areaStyle' => [
                'opacity' => 0.3,
                'color' => 'rgba(227, 73, 2, 0.3)',
            ],
            'emphasis' => [
                'focus' => 'series',
            ],
        ];

        $unitSymbol = Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol();

        $series[] = array_merge_recursive(
            $serie,
            [
                'name' => 'Distance / week',
                'data' => $data[0],
                'yAxisIndex' => 0,
                'label' => [
                    'formatter' => '{@[1]} '.$unitSymbol,
                ],
            ],
        );

        $series[] = array_merge_recursive(
            $serie,
            [
                'name' => 'Time / week',
                'data' => $data[1],
                'yAxisIndex' => 1,
                'label' => [
                    'formatter' => '{@[1]} h',
                ],
            ],
        );

        return [
            'animation' => true,
            'backgroundColor' => null,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => 'single',
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'startValue' => count($weeks),
                    'endValue' => count($weeks) - $zoomValueSpan,
                    'minValueSpan' => $zoomValueSpan,
                    'maxValueSpan' => $zoomValueSpan,
                    'brushSelect' => false,
                    'zoomLock' => true,
                ],
                [
                ],
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 0,
                    ],
                    'data' => $xAxisLabels,
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} '.$unitSymbol,
                    ],
                    'max' => 50 * ceil(max($data[0]) / 50),
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} h',
                    ],
                ],
            ],
            'series' => $series,
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(Weeks $weeks): array
    {
        $distancePerWeek = [];
        $timePerWeek = [];

        /** @var Week $week */
        foreach ($weeks as $week) {
            $distancePerWeek[$week->getId()] = 0;
            $timePerWeek[$week->getId()] = 0;
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $week = $activity->getStartDate()->getYearAndWeekNumberString();
            if (!array_key_exists($week, $distancePerWeek)) {
                continue;
            }

            $distance = $activity->getDistance()->toUnitSystem($this->unitSystem);
            $distancePerWeek[$week] += $distance->toFloat();
            $timePerWeek[$week] += $activity->getMovingTimeInSeconds();
        }

        $distancePerWeek = array_map('round', $distancePerWeek);
        $timePerWeek = array_map(fn (int $time) => round($time / 3600, 1), $timePerWeek);

        return [array_values($distancePerWeek), array_values($timePerWeek)];
    }
}
