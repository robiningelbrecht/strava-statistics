<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

interface Unit extends \Stringable, \JsonSerializable
{
    public function getSymbol(): string;
}
