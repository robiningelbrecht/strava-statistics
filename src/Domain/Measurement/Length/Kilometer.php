<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Length;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;
use App\Domain\Measurement\UnitSystem;

final readonly class Kilometer implements Unit
{
    use MeasurementFromFloat;
    public const float FACTOR_TO_MILES = 0.621371;

    public function getSymbol(): string
    {
        return 'km';
    }

    public function toMiles(): Mile
    {
        return Mile::from($this->value * self::FACTOR_TO_MILES);
    }

    public function toUnitSystem(UnitSystem $unitSystem): Kilometer|Mile
    {
        if (UnitSystem::METRIC === $unitSystem) {
            return $this;
        }

        return $this->toMiles();
    }
}