<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\YearlyDistance;

use App\Domain\Strava\Activity\ReadModel\Activities;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class YearlyDistanceChartBuilder
{
    private function __construct(
        private Activities $activities,
        private UnitSystem $unitSystem,
        private SerializableDateTime $now,
    ) {
    }

    public static function fromActivities(
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
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];

        $xAxisLabels = [];
        foreach ($months as $month) {
            $xAxisLabels = [...$xAxisLabels, ...array_fill(0, 31, $month)];
        }

        $series = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->activities->getUniqueYears() as $year) {
            $series[(string) $year] = [
                'name' => (string) $year,
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => [],
            ];

            $runningSum = 0;
            foreach ($months as $monthNumber => $label) {
                for ($i = 0; $i < 31; ++$i) {
                    $date = SerializableDateTime::fromString(sprintf(
                        '%s-%s-%s',
                        $year,
                        $monthNumber,
                        str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT))
                    );
                    $activitiesOnThisDay = $this->activities->filterOnDate($date);

                    if ($date->isAfter($this->now)) {
                        break 2;
                    }

                    $runningSum += $activitiesOnThisDay->sum(
                        fn (ActivityDetails $activity) => $activity->getDistance()->toUnitSystem($this->unitSystem)->toFloat()
                    );
                    $series[(string) $year]['data'][] = round($runningSum);
                }
            }
        }

        $unitSymbol = Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol();

        return [
            'animation' => true,
            'backgroundColor' => null,
            'grid' => [
                'left' => '40px',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 31,
                    ],
                    'data' => $xAxisLabels,
                ],
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'Distance in '.$unitSymbol,
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                ],
            ],
            'series' => array_values($series),
        ];
    }
}
