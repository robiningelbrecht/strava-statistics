<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Infrastructure\Time\Format\ProvideTimeFormats;

final readonly class HeartRateChart
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
        /** @var non-empty-array<mixed> $heartRateData */
        $heartRateData = $this->heartRateStream->getData();

        // Shave of leading zero's. We don't care about those hear rates.
        while (!empty($heartRateData) && 0 === reset($heartRateData)) {
            array_shift($heartRateData);
        }

        if (empty($heartRateData)) {
            return [];
        }

        foreach (array_keys($heartRateData) as $timeInSeconds) {
            $xAxisData[] = $this->formatDurationForChartLabel($timeInSeconds);
        }

        return [
            'grid' => [
                'left' => '9%',
                'right' => '0%',
                'bottom' => '7%',
                'height' => '325px',
                'containLabel' => false,
            ],
            'color' => '#DF584A',
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
                'min' => max(0, floor((min($heartRateData) - 10) / 10) * 10),
                'max' => ceil((max($heartRateData) + 10) / 10) * 10,
                'axisLabel' => [
                    'showMaxLabel' => false,
                ],
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
                            'color' => '#FFFFFF',
                            'fontWeight' => 'bold',
                            'textBorderColor' => 'none',
                        ],
                        'symbol' => 'none',
                        'lineStyle' => [
                            'type' => 7,
                            'width' => 1.5,
                            'color' => '#FFFFFF',
                        ],
                        'data' => [
                            [
                                'type' => 'max',
                                'name' => 'Max',
                                'label' => [
                                    'formatter' => 'max {c}',
                                ],
                            ],
                            [
                                'type' => 'average',
                                'name' => 'Avg',
                                'label' => [
                                    'formatter' => 'avg {c}',
                                ],
                            ],
                        ],
                    ],
                    'markArea' => [
                        'data' => [
                            [
                                [
                                    'itemStyle' => [
                                        'color' => '#303030',
                                    ],
                                ],
                                [
                                    'x' => '100%',
                                ],
                            ],
                        ],
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'areaStyle' => [
                        'opacity' => 0.3,
                    ],
                ],
            ],
        ];
    }
}
