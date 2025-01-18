<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Activity;

final readonly class HeartRate
{
    private function __construct(
        private string $time,
        private int $rate,
        private Activity $activity,
    ) {
    }

    public static function fromState(
        string $time,
        int $rate,
        Activity $activity,
    ): self {
        return new self(
            time: $time,
            rate: $rate,
            activity: $activity
        );
    }

    public function getTime(): string
    {
        return $this->time;
    }

    public function getRate(): int
    {
        return $this->rate;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }
}
