<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Mass;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class Gram implements Unit
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
}
