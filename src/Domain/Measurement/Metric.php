<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

interface Metric
{
    public function toImperial(): Unit;
}
