<?php

namespace App\Domain\Strava\Activity\Eddington;

final class EddingtonChartBuilder
{
    private function __construct(
        private readonly Eddington $eddington,
    ) {
    }

    public static function fromEddington(Eddington $eddington): self
    {
        return new self($eddington);
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $longestDistanceInADay = $this->eddington->getLongestDistanceInADay();
        /** @var non-empty-array<mixed> $timesCompletedData */
        $timesCompletedData = $this->eddington->getTimesCompletedData();
        $eddingtonNumber = $this->eddington->getNumber();

        $yAxisMaxValue = ceil(max($timesCompletedData) / 30) * 30;
        $yAxisInterval = ceil(($yAxisMaxValue / 5) / 30) * 30;

        $timesCompletedDataForChart = $timesCompletedData;
        $timesCompletedDataForChart[$eddingtonNumber] = [
            'value' => $timesCompletedData[$eddingtonNumber],
            'itemStyle' => [
                'color' => 'rgba(227, 73, 2, 0.8)',
            ],
        ];

        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => false,
            ],
            'xAxis' => [
                'data' => array_map(fn (int $distance) => $distance.'km', range(1, $longestDistanceInADay)),
                'type' => 'category',
                'axisTick' => [
                    'alignWithLabel' => true,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => true,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => $yAxisInterval,
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => $yAxisInterval,
                ],
            ],
            'series' => [
                [
                    'name' => 'Times completed',
                    'yAxisIndex' => 0,
                    'type' => 'bar',
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'markPoint' => [
                        'symbol' => 'pin',
                        'symbolOffset' => [
                            0,
                            -5,
                        ],
                        'itemStyle' => [
                            'color' => 'rgba(227, 73, 2, 0.8)',
                        ],
                        'data' => [
                            [
                                'value' => $eddingtonNumber,
                                'coord' => [
                                    $eddingtonNumber - 1,
                                    $timesCompletedData[$eddingtonNumber] - 1,
                                ],
                            ],
                        ],
                    ],
                    'data' => array_values($timesCompletedDataForChart),
                ],
                [
                    'name' => 'Eddington',
                    'yAxisIndex' => 1,
                    'zlevel' => 1,
                    'type' => 'line',
                    'smooth' => false,
                    'showSymbol' => false,
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => '#E34902',
                    ],
                    'data' => range(1, $longestDistanceInADay),
                ],
            ],
        ];
    }
}
