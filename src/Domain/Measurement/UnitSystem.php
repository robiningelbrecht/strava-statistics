<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

enum UnitSystem: string
{
    case METRIC = 'metric';
    case IMPERIAL = 'imperial';
}
