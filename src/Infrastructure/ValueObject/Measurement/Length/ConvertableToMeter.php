<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Length;

interface ConvertableToMeter
{
    public function toMeter(): Meter;
}
