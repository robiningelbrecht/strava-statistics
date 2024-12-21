<?php

namespace App\Domain\Strava\Activity\DaytimeStats;

final class DaytimeStatsChartsBuilder
{
    private function __construct(
        private readonly DaytimeStats $daytimeStats,
    ) {
    }

    public static function fromDaytimeStats(
        DaytimeStats $daytimeStats,
    ): self {
        return new self($daytimeStats);
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{d}%',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{daytime|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'daytime' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => $this->getData(),
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        $data = [];
        foreach ($this->daytimeStats->getData() as $statistic) {
            $data[] = [
                'value' => $statistic['percentage'],
                'name' => $statistic['daytime']->getEmoji().' '.$statistic['daytime']->value,
            ];
        }

        return $data;
    }
}
