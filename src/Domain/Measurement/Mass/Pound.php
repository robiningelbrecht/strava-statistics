<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Mass;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class Pound implements Unit
{
    use MeasurementFromFloat;

    public function getSymbol(): string
    {
        return 'lb';
    }

    public function toGram(): Gram
    {
        return Gram::from($this->value * 453.59237);
    }
}
