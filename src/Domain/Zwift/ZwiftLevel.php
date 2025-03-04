<?php

declare(strict_types=1);

namespace App\Domain\Zwift;

use App\Infrastructure\ValueObject\Number\PositiveInteger;

final readonly class ZwiftLevel extends PositiveInteger
{
    private const int MAX_LEVEL = 100;

    protected function validate(int $value): void
    {
        if ($value >= 1 && $value <= self::MAX_LEVEL) {
            return;
        }

        throw new \InvalidArgumentException('ZwiftLevel must be a number between 1 and 100');
    }

    public function getProgressPercentage(): int
    {
        return (int) round($this->getValue() / self::MAX_LEVEL * 100);
    }
}
