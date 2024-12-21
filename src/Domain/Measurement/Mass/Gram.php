<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Mass;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Metric;
use App\Domain\Measurement\Unit;

final readonly class Gram implements Unit, Metric
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'gr';
    }

    public function toKilogram(): Kilogram
    {
        return Kilogram::from($this->value / 1000);
    }

    public function toImperial(): Unit
    {
        return Pound::from($this->value * 0.00220462262);
    }
}
