<?php

declare(strict_types=1);

namespace App\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class EFtpHistoryChart
{
    private function __construct(
        private EFtpCalculator $eftpCalculator,
        private ActivityType $activityType,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        EFtpCalculator $eftpCalculator,
        ActivityType $activityType,
        SerializableDateTime $now,
    ): self {
        return new self(
            eftpCalculator: $eftpCalculator,
            activityType: $activityType,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $today = $this->now->format('Y-m-d');
        $eftpDates = $this->eftpCalculator->findEFtpDates($this->activityType);

        $dates = array_unique(array_merge($eftpDates, [$today]));
        sort($dates);

        $eftpResults = [];
        $relativeEftpResults = [];

        foreach ($dates as $date) {
            $eftpForDate = $this->eftpCalculator->findForActivityType(
                $this->activityType,
                SerializableDateTime::fromString($date)
            );

            if (!$eftpForDate) {
                continue;
            }

            $lastEftp = end($eftpResults);
            $lastRelative = end($relativeEftpResults);

            if (!$lastEftp
                || $today === $date
                || $lastEftp[1] !== $eftpForDate->getEFtp()
            ) {
                $eftpResults[] = [$date, $eftpForDate->getEFtp()];
            }

            if (!$lastRelative
                || $today === $date
                || $lastRelative[1] !== $eftpForDate->getRelativeEftp()
            ) {
                $relativeEftpResults[] = [$date, $eftpForDate->getRelativeEftp()];
            }
        }

        if (empty($eftpResults)) {
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
                    'min' => min(array_column($eftpResults, 1)) - 10,
                ],
                !empty($relativeEftpResults) ? [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w/kg',
                    ],
                    'min' => round(min(array_column($relativeEftpResults, 1)) - 1, 1),
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
                        ...$eftpResults,
                    ],
                ],
                !empty($relativeEftpResults) ? [
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
                        ...$relativeEftpResults,
                    ],
                ] : [],
            ],
        ];
    }
}
