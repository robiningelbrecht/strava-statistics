<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Length;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class Meter implements Unit, Metric, ConvertableToMeter
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'm';
    }

    public function toFoot(): Foot
    {
        return Foot::from($this->value * 3.2805);
    }

    public function toUnitSystem(UnitSystem $unitSystem): Meter|Foot
    {
        if (UnitSystem::METRIC === $unitSystem) {
            return $this;
        }

        return $this->toFoot();
    }

    public function toImperial(): Unit
    {
        return $this->toFoot();
    }

    public function toMeter(): Meter
    {
        return $this;
    }
}
