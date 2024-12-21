<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Measurement\Imperial;
use App\Domain\Measurement\Metric;
use App\Domain\Measurement\Unit;
use App\Domain\Measurement\UnitSystem;

final readonly class ConvertMeasurementTwigExtension
{
    public function __construct(
        private UnitSystem $unitSystem,
    ) {
    }

    public function doConversion(Unit $measurement): Unit
    {
        if (UnitSystem::IMPERIAL === $this->unitSystem && $measurement instanceof Metric) {
            return $measurement->toImperial();
        }
        if (UnitSystem::METRIC === $this->unitSystem && $measurement instanceof Imperial) {
            return $measurement->toMetric();
        }

        return $measurement;
    }
}
