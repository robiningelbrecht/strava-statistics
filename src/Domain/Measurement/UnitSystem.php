<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

use App\Domain\Measurement\Length\Foot;
use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Measurement\Length\Mile;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Measurement\Velocity\MilesPerHour;

enum UnitSystem: string
{
    case METRIC = 'metric';
    case IMPERIAL = 'imperial';

    public function distance(float $value): Kilometer|Mile
    {
        if (UnitSystem::METRIC === $this) {
            return Kilometer::from($value);
        }

        return Mile::from($value);
    }

    public function elevation(float $value): Meter|Foot
    {
        if (UnitSystem::METRIC === $this) {
            return Meter::from($value);
        }

        return Foot::from($value);
    }

    public function speed(float $value): KmPerHour|MilesPerHour
    {
        if (UnitSystem::METRIC === $this) {
            return KmPerHour::from($value);
        }

        return MilesPerHour::from($value);
    }
}
