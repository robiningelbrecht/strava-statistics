<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Mass;

use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Metric;
use App\Domain\Measurement\Unit;

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
}
