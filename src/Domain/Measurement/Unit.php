<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

interface Unit extends \Stringable, \JsonSerializable
{
    public static function from(float $value): self;

    public static function zero(): self;

    public function isZeroOrLower(): bool;

    public function getSymbol(): string;

    public function toFloat(): float;
}
