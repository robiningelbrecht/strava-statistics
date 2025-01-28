<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Infrastructure\Time\Format\ProvideTimeFormats;

final readonly class HeartRateChartBuilder
{
    use ProvideTimeFormats;

    private function __construct(
        private ActivityStream $heartRateStream,
    ) {
    }

    public static function create(
        ActivityStream $heartRateStream,
    ): self {
        return new self($heartRateStream);
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $xAxisData = [];
        $heartRateData = $this->heartRateStream->getData();

        foreach (array_keys($heartRateData) as $timeInSeconds) {
            $xAxisData[] = $this->formatDurationForChartLabel($timeInSeconds);
        }

        return [
            'grid' => [
                'top' => '3%',
                'left' => '3%',
                'right' => '3%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'color' => '#BD2D22',
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $xAxisData,
                'axisTick' => [
                    'show' => false,
                ],
            ],
            'yAxis' => [
                'type' => 'value',
                'name' => 'bpm',
                'nameRotate' => 90,
                'nameLocation' => 'middle',
                'nameGap' => 35,
                'min' => floor((min($heartRateData) - 10) / 10) * 10,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => 'bpm: <b>{c}</b>',
            ],
            'series' => [
                [
                    'data' => $heartRateData,
                    'type' => 'line',
                    'symbol' => 'none',
                    'smooth' => false,
                    'markLine' => [
                        'silent' => true,
                        'label' => [
                            'position' => 'insideMiddleTop',
                            'rich' => [
                                'bpm' => [
                                    'fontWeight' => 'bold',
                                ],
                            ],
                        ],
                        'symbol' => 'none',
                        'lineStyle' => [
                            'type' => 7,
                            'width' => 1.5,
                            'color' => '#303030',
                        ],
                        'data' => [
                            [
                                'type' => 'max',
                                'name' => 'Max',
                                'label' => [
                                    'formatter' => 'max {bpm|{c}}',
                                ],
                            ],
                            [
                                'type' => 'average',
                                'name' => 'Avg',
                                'label' => [
                                    'formatter' => 'avg {bpm|{c}}',
                                ],
                            ],
                        ],
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'areaStyle' => [
                        'opacity' => 0.8,
                    ],
                ],
            ],
        ];
    }
}
