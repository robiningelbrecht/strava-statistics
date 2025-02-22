<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Activity;

final readonly class PowerOutput
{
    private function __construct(
        private int $timeInSeconds,
        private string $formattedTime,
        private int $power,
        private float $relativePower,
        private ?Activity $activity = null,
    ) {
    }

    public static function fromState(
        int $timeInSeconds,
        string $formattedTime,
        int $power,
        float $relativePower,
        ?Activity $activity = null,
    ): self {
        return new self(
            timeInSeconds: $timeInSeconds,
            formattedTime: $formattedTime,
            power: $power,
            relativePower: $relativePower,
            activity: $activity
        );
    }

    public function getTimeInSeconds(): int
    {
        return $this->timeInSeconds;
    }

    public function getFormattedTime(): string
    {
        return $this->formattedTime;
    }

    public function getPower(): int
    {
        return $this->power;
    }

    public function getRelativePower(): float
    {
        return $this->relativePower;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }
}
