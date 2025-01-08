<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Eddington;

final readonly class EddingtonHistoryChartBuilder
{
    private function __construct(
        private Eddington $eddington,
    ) {
    }

    public static function create(
        Eddington $eddington,
    ): self {
        return new self(
            eddington: $eddington,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $data = [];

        $markPoints = [];
        foreach ($this->eddington->getEddingtonHistory() as $eddington => $on) {
            $data[] = [$on->format('Y-m-d'), $eddington];
            if (0 !== $eddington % 10) {
                continue;
            }
            $markPoints[] = [
                'value' => $eddington,
                'coord' => [
                    $on->format('Y-m-d'),
                    $eddington,
                ],
            ];
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'top' => '3%',
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '{DD}',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
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
                    'minInterval' => 1,
                    'splitLine' => [
                        'show' => false,
                    ],
                    'min' => 0,
                ],
            ],
            'series' => [
                [
                    'name' => 'Eddington',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => true,
                    'lineStyle' => [
                        'width' => 2,
                    ],
                    'showSymbol' => false,
                    'data' => $data,
                    'markPoint' => [
                        'symbol' => 'pin',
                        'symbolSize' => 0,
                        'symbolOffset' => [
                            0,
                            -15,
                        ],
                        'silent' => true,
                        'itemStyle' => [
                            'color' => 'transparent',
                        ],
                        'label' => [
                            'color' => '#E34902',
                        ],
                        'data' => $markPoints,
                    ],
                ],
            ],
        ];
    }
}
