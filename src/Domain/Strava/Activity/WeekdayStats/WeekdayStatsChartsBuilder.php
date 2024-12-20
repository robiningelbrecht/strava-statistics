<?php

namespace App\Domain\Strava\Activity\WeekdayStats;

final class WeekdayStatsChartsBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly WeekdayStats $weekdayStats,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromWeekdayStats(
        WeekdayStats $weekdayStats,
    ): self {
        return new self($weekdayStats);
    }

    public function withAnimation(bool $flag): self
    {
        $this->animation = $flag;

        return $this;
    }

    public function withoutBackgroundColor(): self
    {
        $this->backgroundColor = null;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
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
                        'formatter' => "{weekday|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'weekday' => [
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
        foreach ($this->weekdayStats->getData() as $weekday => $statistic) {
            $data[] = [
                'value' => $statistic['percentage'],
                'name' => $weekday,
            ];
        }

        return $data;
    }
}
