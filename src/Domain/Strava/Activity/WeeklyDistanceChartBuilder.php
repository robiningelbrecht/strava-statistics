<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Calendar\Week;
use App\Domain\Strava\Calendar\Weeks;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class WeeklyDistanceChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;
    private bool $useDataZoom;
    private bool $withAverageTimes;

    private function __construct(
        private readonly Activities $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->animation = true;
        $this->backgroundColor = '#ffffff';
        $this->useDataZoom = true;
        $this->withAverageTimes = true;
    }

    public static function fromActivities(Activities $activities, SerializableDateTime $now): self
    {
        return new self($activities, $now);
    }

    public function withoutBackgroundColor(): self
    {
        $this->backgroundColor = null;

        return $this;
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
        if (!$this->useDataZoom) {
            $weeks = $weeks->slice(($zoomValueSpan + 1) * -1);
        }
        $data = $this->getData($weeks);
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

        $series[] = array_merge_recursive(
            $serie,
            [
                'name' => 'Distance / week',
                'data' => $data[0],
                'yAxisIndex' => 0,
                'label' => [
                    'formatter' => '{@[1]} km',
                ],
            ],
        );

        if ($this->withAverageTimes) {
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
        }

        return [
            'animation' => $this->animation,
            'backgroundColor' => $this->backgroundColor,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => $this->useDataZoom ? '50px' : '3%',
                'containLabel' => true,
            ],
            'legend' => $this->withAverageTimes ? [
                'show' => true,
                'selectedMode' => 'single',
            ] : null,
            'dataZoom' => $this->useDataZoom ? [
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
            ] : [],
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
                        'formatter' => '{value} km',
                    ],
                    'max' => 50 * ceil(max($data[0]) / 50),
                ],
                $this->withAverageTimes ? [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} h',
                    ],
                ] : [],
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
            $distancePerWeek[$week] += $activity->getDistance()->toFloat();
            $timePerWeek[$week] += $activity->getMovingTimeInSeconds();
        }

        $distancePerWeek = array_map('round', $distancePerWeek);
        $timePerWeek = array_map(fn (int $time) => round($time / 3600, 1), $timePerWeek);

        return [array_values($distancePerWeek), array_values($timePerWeek)];
    }
}
