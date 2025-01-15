<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Imperial;
use App\Infrastructure\ValueObject\Measurement\MeasurementFromFloat;
use App\Infrastructure\ValueObject\Measurement\Unit;

final readonly class Pound implements Unit, Imperial
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

    public function toMetric(): Unit
    {
        return $this->toGram();
    }
}
