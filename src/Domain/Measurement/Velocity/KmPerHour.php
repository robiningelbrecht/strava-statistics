<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Velocity;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class KmPerHour implements Unit
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'km/h';
    }

    public function toMph(): MilesPerHour
    {
        return MilesPerHour::from($this->value * Kilometer::FACTOR_TO_MILES);
    }
}
