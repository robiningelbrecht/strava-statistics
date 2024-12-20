<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

enum MeasurementSystem: string
{
    case METRIC = 'metric';
    case IMPERIAL = 'imperial';
}
