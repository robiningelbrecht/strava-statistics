<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Length;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;
use App\Domain\Measurement\UnitSystem;

final readonly class Meter implements Unit
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
}
