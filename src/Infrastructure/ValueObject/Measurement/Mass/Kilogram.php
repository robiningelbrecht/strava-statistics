<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Metric;
use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class Kilogram implements Unit, Metric
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'kg';
    }

    public function toGram(): Gram
    {
        return Gram::from($this->value * 1000);
    }

    public function toPound(): Pound
    {
        return Pound::from($this->value * 2.20462);
    }

    public function toImperial(): Unit
    {
        return $this->toPound();
    }

    public function toUnitSystem(UnitSystem $unitSystem): Kilogram|Pound
    {
        if (UnitSystem::METRIC === $unitSystem) {
            return $this;
        }

        return $this->toPound();
    }
}
