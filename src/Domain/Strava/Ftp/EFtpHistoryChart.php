<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class EFtpHistoryChart
{
    private function __construct(
        private EFtps $eftps,
    ) {
    }

    public static function create(
        EFtps $eftps,
    ): self {
        return new self(
            eftps: $eftps
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $uniqueDates = [];
        foreach ($this->eftps as $eftp) {
            $dateKey = $eftp->getSetOn()->format('Y-m-d');
            $uniqueDates[$dateKey] = $dateKey;
        }

        ksort($uniqueDates);
        $eftp = [];
        $relative_eftp = [];

        foreach ($uniqueDates as $date) {
            $currentDate = SerializableDateTime::fromString($date);
            $eftpForDate = $this->eftps->findForDate($currentDate);

            if ($eftpForDate) {
                $lastEftp = end($eftp);
                if (!$lastEftp || $lastEftp[1] !== $eftpForDate->getEftp()) {
                    $eftp[] = [$date, $eftpForDate->getEftp()];
                }

                $lastRelative = end($relative_eftp);
                if (!$lastRelative || $lastRelative[1] !== $eftpForDate->getRelativeEftp()) {
                    $relative_eftp[] = [$date, $eftpForDate->getRelativeEftp()];
                }
            }
        }

        if (empty($eftp)) {
            return [];
        }

        return [
            'animation' => true,
            'backgroundColor' => null,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'top' => '2%',
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
                            'day' => '',
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
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                    'min' => min(array_column($eftp, 1)) - 10,
                ],
                !empty($relative_eftp) ? [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w/kg',
                    ],
                    'min' => round(min(array_column($relative_eftp, 1)) - 1, 1),
                ] : [],
            ],
            'series' => [
                [
                    'name' => 'eFTP watts',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'yAxisIndex' => 0,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$eftp,
                    ],
                ],
                !empty($relative_eftp) ? [
                    'name' => 'eFTP w/kg',
                    'type' => 'line',
                    'smooth' => false,
                    'color' => [
                        '#3AA272',
                    ],
                    'yAxisIndex' => 1,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$relative_eftp,
                    ],
                ] : [],
            ],
        ];
    }
}
