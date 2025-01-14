<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ReadModel\ActivityDetails;

final readonly class PowerOutput
{
    private function __construct(
        private string $time,
        private int $power,
        private float $relativePower,
        private ?ActivityDetails $activity = null,
    ) {
    }

    public static function fromState(
        string $time,
        int $power,
        float $relativePower,
        ?ActivityDetails $activity = null,
    ): self {
        return new self(
            time: $time,
            power: $power,
            relativePower: $relativePower,
            activity: $activity
        );
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getPower(): int
    {
        return $this->power;
    }

    public function getRelativePower(): float
    {
        return $this->relativePower;
    }

    public function getActivity(): ?ActivityDetails
    {
        return $this->activity;
    }
}
