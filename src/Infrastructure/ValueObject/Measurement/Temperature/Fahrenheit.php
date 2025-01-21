<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Temperature;

use App\Infrastructure\ValueObject\Measurement\Imperial;
use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Fahrenheit implements Unit, Imperial
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return '°F';
    }

    public function toMetric(): Unit
    {
        return Celsius::from(round(5 / 9 * ($this->value - 32), 2));
    }
}
