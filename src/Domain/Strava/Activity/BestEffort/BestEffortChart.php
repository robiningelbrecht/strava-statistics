<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityType;

final readonly class BestEffortChart
{
    private function __construct(
        private ActivityType $activityType,
        private ActivityBestEfforts $bestEfforts,
    ) {
    }

    public static function create(
        ActivityType $activityType,
        ActivityBestEfforts $bestEfforts,
    ): self {
        return new self(
            activityType: $activityType,
            bestEfforts: $bestEfforts
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];

        foreach ($this->bestEfforts->getUniqueSportTypes() as $sportType) {
            $series[] = [
                'name' => $sportType->value,
                'type' => 'bar',
                'barGap' => 0,
                'emphasis' => [
                    'focus' => 'none',
                ],
                'label' => [
                    'show' => false,
                ],
                'data' => $this->bestEfforts->getBySportType($sportType)->map(fn (ActivityBestEffort $bestEffort) => $bestEffort->getTimeInSeconds()),
            ];
        }

        return [
            'backgroundColor' => '#ffffff',
            'animation' => true,
            'color' => ['#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '2%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'none',
                ],
                'valueFormatter' => 'formatSeconds',
            ],
            'legend' => [
                'show' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => $this->activityType->getDistancesForBestEffortCalculation(),
                ],
            ],
            'yAxis' => [
                'type' => 'value',
                'axisLabel' => [
                    'formatter' => 'formatSeconds',
                ],
            ],
            'series' => $series,
        ];
    }
}
