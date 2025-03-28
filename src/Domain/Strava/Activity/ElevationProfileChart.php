<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class ElevationProfileChart
{
    private function __construct(
        private ActivityStream $distanceStream,
        private ActivityStream $altitudeStream,
        private UnitSystem $unitSystem,
    ) {
    }

    public static function create(
        ActivityStream $distanceStream,
        ActivityStream $altitudeStream,
        UnitSystem $unitSystem,
    ): self {
        return new self(
            distanceStream: $distanceStream,
            altitudeStream: $altitudeStream,
            unitSystem: $unitSystem,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $elevationSymbol = $this->unitSystem->elevationSymbol();

        return [
            'grid' => [
                'left' => '9%',
                'right' => '0%',
                'bottom' => '7%',
                'containLabel' => false,
            ],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'formatter' => '{value} '.$distanceSymbol,
                ],
                'data' => array_map(
                    function (int $distanceInMeter) {
                        $distance = Kilometer::from($distanceInMeter / 1000)->toUnitSystem($this->unitSystem)->toFloat();

                        return $distance < 1 ? round($distance, 1) : round($distance);
                    },
                    $distanceStreamData,
                ),
                'splitLine' => [
                    'show' => true,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => true,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} '.$elevationSymbol,
                    ],
                ],
            ],
            'series' => [
                [
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
                    'data' => array_map(
                        fn (int $altitude) => round(Meter::from($altitude)->toUnitSystem($this->unitSystem)->toFloat()),
                        $elevationStreamData
                    ),
                    'type' => 'line',
                    'name' => 'Elevation',
                    'symbol' => 'none',
                    'color' => '#D9D9D9',
                    'smooth' => true,
                    'emphasis' => [
                        'disabled' => true,
                    ],
                    'areaStyle' => [
                    ],
                    'lineStyle' => [
                        'width' => 0,
                    ],
                ],
            ],
        ];
    }
}
