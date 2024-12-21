<?php

declare(strict_types=1);

namespace App\Domain\Measurement\Length;

use App\Domain\Measurement\Imperial;
use App\Domain\Measurement\MeasurementFromFloat;
use App\Domain\Measurement\Unit;

final readonly class Mile implements Unit, Imperial
{
    use MeasurementFromFloat;

    public const float FACTOR_TO_KM = 1.60934;

    public function getSymbol(): string
    {
        return 'mi';
    }

    public function toKilometer(): Kilometer
    {
        return Kilometer::from($this->value * self::FACTOR_TO_KM);
    }

    public function toMetric(): Unit
    {
        return $this->toKilometer();
    }
}
