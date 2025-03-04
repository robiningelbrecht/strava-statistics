<?php

declare(strict_types=1);

namespace App\Domain\Zwift;

use App\Infrastructure\ValueObject\Number\PositiveInteger;

final readonly class ZwiftRacingScore extends PositiveInteger
{
    private const int MAX_SCORE = 1000;

    protected function validate(int $value): void
    {
        if ($value <= self::MAX_SCORE) {
            return;
        }

        throw new \InvalidArgumentException('ZwiftRacingScore must be a number between 0 and 1000');
    }
}
