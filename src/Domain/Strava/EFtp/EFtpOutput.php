<?php

namespace App\Domain\Strava\EFtp;

final readonly class EFtpOutput
{
    private function __construct(
        private int $timeIntervalInSeconds,
        private string $formattedTimeInterval,
        private int $power,
        private float $relativePower,
    ) {
    }

    public static function fromState(
        int $timeIntervalInSeconds,
        string $formattedTimeInterval,
        int $power,
        float $relativePower,
    ): self {
        return new self(
            timeIntervalInSeconds: $timeIntervalInSeconds,
            formattedTimeInterval: $formattedTimeInterval,
            power: $power,
            relativePower: $relativePower,
        );
    }

    public function getTimeIntervalInSeconds(): int
    {
        return $this->timeIntervalInSeconds;
    }

    public function getFormattedTimeInterval(): string
    {
        return $this->formattedTimeInterval;
    }

    public function getPower(): int
    {
        return $this->power;
    }

    public function getRelativePower(): float
    {
        return $this->relativePower;
    }
}
