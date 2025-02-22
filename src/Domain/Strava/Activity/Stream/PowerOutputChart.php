<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

final readonly class PowerOutputChart
{
    private function __construct(
        private PowerOutputs $bestPowerOutputs,
    ) {
    }

    public static function create(
        PowerOutputs $bestPowerOutputs,
    ): self {
        return new self($bestPowerOutputs);
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $powerOutputs = array_values($this->bestPowerOutputs->map(fn (PowerOutput $powerOutput) => $powerOutput->getPower()));
        // @phpstan-ignore-next-line
        $yAxisOneMaxValue = ceil(max($powerOutputs) / 100) * 100;
        $yAxisOneInterval = $yAxisOneMaxValue / 5;

        return [
            'animation' => true,
            'backgroundColor' => null,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => false,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
                // 'formatter' => '<div style="width: 130px"><div style="display:flex;align-items:center;justify-content:space-between;"><div style="display:flex;align-items:center;column-gap:6px"><div style="border-radius:10px;width:10px;height:10px;background-color:#e34902"></div><div style="font-size:14px;color:#666;font-weight:400">Watt</div></div><div style="font-size:14px;color:#666;font-weight:900">{c0}</div></div><div style="display:flex;align-items:center;justify-content:space-between"><div style="display:flex;align-items:center;column-gap:6px"><div style="border-radius:10px;width:10px;height:10px;background-color:rgba(227,73,2,.7)"></div><div style="font-size:14px;color:#666;font-weight:400">Watt per kg</div></div><div style="font-size:14px;color:#666;font-weight:900">{c1}</div></div></div>',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'interval' => 0,
                ],
                'axisTick' => [
                    'show' => false,
                ],
                'data' => [
                    '1s',
                    '5s',
                    '',
                    '15s',
                    '',
                    '',
                    '1m',
                    '2m',
                    '',
                    '',
                    '5m',
                    '',
                    '8m',
                    '',
                    '',
                    '20m',
                    '30m',
                    '',
                    '',
                    '1h',
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                    'max' => $yAxisOneMaxValue,
                    'interval' => $yAxisOneInterval,
                ],
            ],
            'series' => [
                [
                    'type' => 'line',
                    'name' => 'Watt',
                    'smooth' => true,
                    'symbol' => 'none',
                    'yAxisIndex' => 0,
                    'data' => $powerOutputs,
                ],
            ],
        ];
    }
}
