<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Length;

use App\Domain\Measurement\Imperial;
use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class Foot implements Unit, Imperial
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'ft';
    }

    public function toMeter(): Meter
    {
        return Meter::from($this->value * 0.3048);
    }

    public function toMetric(): Unit
    {
        return $this->toMeter();
    }
}
