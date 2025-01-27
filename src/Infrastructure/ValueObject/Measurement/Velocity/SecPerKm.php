<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class SecPerKm implements Unit
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'sec/km';
    }

    public function toMetersPerSecond(): MetersPerSecond
    {
        if (0.0 === $this->value) {
            return MetersPerSecond::from(0);
        }

        return MetersPerSecond::from(round((1 / $this->value) * 1000, 3));
    }
}
