<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use Carbon\Carbon;

final readonly class EFtpHistoryChart
{
    private function __construct(
        private Activities $activities,
        private SerializableDateTime $now,
    ) {
    }

    public static function create(
        Activities $activities,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $activitiesWithEFTP = $this->activities->filter(fn (Activity $activity) => $activity->getEFTP() !== null);

        $uniqueDates = [];
        foreach ($activitiesWithEFTP as $activity) {
            $dateKey = $activity->getStartDate()->format('Y-m-d');
            $uniqueDates[$dateKey] = $dateKey;
        }
        ksort($uniqueDates);
        $eftp = [];
        $relative_eftp = [];

        // Käydään läpi jokainen uniikki päivämäärä
        foreach ($uniqueDates as $date) {
            $currentDate = Carbon::parse($date);
            $startDate = $currentDate->copy()->subWeeks(8);

            $maxEftp = null;
            $maxRelativeEftp = null;
            
            foreach ($activitiesWithEFTP as $activity) {
                $activityDate = Carbon::instance($activity->getStartDate());

                if ($activityDate->between($startDate, $currentDate)) {
                    $eftpValue = $activity->getEFTP();
                    $relativeEftpValue = $activity->getRelativeEftp();

                    if ($maxEftp === null || $eftpValue > $maxEftp) {
                        $maxEftp = $eftpValue;
                    }
                    
                    if ($maxRelativeEftp === null || $relativeEftpValue > $maxRelativeEftp) {
                        $maxRelativeEftp = $relativeEftpValue;
                    }
                }
            }

            $lastEftp = end($eftp);
            if (!$lastEftp || $lastEftp[1] !== $maxEftp) {
                $eftp[] = [$date, $maxEftp];
            }

            $lastRelative = end($relative_eftp);
            if (!$lastRelative || $lastRelative[1] !== $maxRelativeEftp) {
                $relative_eftp[] = [$date, $maxRelativeEftp];
            }
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
                    'min' => min(array_column($relative_eftp, 1)) - 1,
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
                        ...$eftp
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
