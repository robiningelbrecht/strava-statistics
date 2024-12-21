<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

interface Imperial
{
    public function toMetric(): Unit;
}
