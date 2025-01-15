<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

interface Metric
{
    public function toImperial(): Unit;
}
