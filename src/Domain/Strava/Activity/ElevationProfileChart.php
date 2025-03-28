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
        $distanceStreamData = $this->distanceStream->getData();
        $elevationStreamData = $this->altitudeStream->getData();

        $downSampledData = $this->downSampledData();

        $distanceStreamData = array_map(
            fn (array $array) => $array[0],
            $downSampledData,
        );
        $elevationStreamData = array_map(
            fn (array $array) => $array[1],
            $downSampledData,
        );
        $distanceStreamData[0] = 0;

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

    private function downSampledData(): array
    {
        // Convert Strava API data into (distance, altitude) pairs
        $rawPoints = [];

        $distances = $this->distanceStream->getData();
        $altitudes = $this->altitudeStream->getData();

        foreach ($distances as $i => $distance) {
            $rawPoints[] = [$distance, $altitudes[$i]];
        }

        $epsilon = 0.5; // Adjust to control the level of simplification

        return $this->rdpSimplify($rawPoints, $epsilon);
    }

    private function perpendicularDistance($point, $lineStart, $lineEnd): float|int
    {
        [$x0, $y0] = $point;
        [$x1, $y1] = $lineStart;
        [$x2, $y2] = $lineEnd;

        $num = abs(($y2 - $y1) * $x0 - ($x2 - $x1) * $y0 + $x2 * $y1 - $y2 * $x1);
        $den = sqrt(pow($y2 - $y1, 2) + pow($x2 - $x1, 2));

        return 0 == $den ? 0 : $num / $den;
    }

    private function rdpSimplify($points, $epsilon)
    {
        if (count($points) < 3) {
            return $points;
        }

        $dmax = 0;
        $index = 0;
        $end = count($points) - 1;

        for ($i = 1; $i < $end; ++$i) {
            $d = $this->perpendicularDistance($points[$i], $points[0], $points[$end]);
            if ($d > $dmax) {
                $index = $i;
                $dmax = $d;
            }
        }

        if ($dmax > $epsilon) {
            $firstHalf = $this->rdpSimplify(array_slice($points, 0, $index + 1), $epsilon);
            $secondHalf = $this->rdpSimplify(array_slice($points, $index), $epsilon);

            return array_merge(array_slice($firstHalf, 0, -1), $secondHalf);
        } else {
            return [$points[0], $points[$end]];
        }
    }
}
