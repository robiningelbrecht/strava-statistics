<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Velocity;

use App\Domain\Measurement\Length\Mile;
use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class MilesPerHour implements Unit
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'mph';
    }

    public function toKmH(): KmPerHour
    {
        return KmPerHour::from($this->value * Mile::FACTOR_TO_KM);
    }
}
