<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Number;

readonly class PositiveInteger
{
    final private function __construct(
        private int $value,
    ) {
        if ($this->value < 0) {
            throw new \InvalidArgumentException(sprintf('Value must be a positive integer, got: %d', $this->value));
        }

        $this->validate($this->value);
    }

    public static function fromInt(int $value): static
    {
        return new static($value);
    }

    public static function fromOptionalInt(?int $value): ?static
    {
        if (null === $value) {
            return null;
        }

        return static::fromInt($value);
    }

    public static function fromOptionalString(?string $value): ?static
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be an integer, got "%s"', $value));
        }

        return static::fromInt((int) $value);
    }

    protected function validate(int $value): void
    {
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
