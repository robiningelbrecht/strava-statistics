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
        return 'min/km';
    }
}