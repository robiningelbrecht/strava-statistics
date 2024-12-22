<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

trait MeasurementFromFloat
{
    private function __construct(
        private readonly float $value,
    ) {
    }

    public static function from(float $value): self
    {
        return new self($value);
    }

    public static function zero(): self
    {
        return self::from(0);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->toFloat();
    }

    public function jsonSerialize(): float
    {
        return $this->toFloat();
    }
}
