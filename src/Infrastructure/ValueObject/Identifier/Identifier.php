<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Identifier;

use App\Infrastructure\ValueObject\String\NonEmptyStringLiteral;

abstract readonly class Identifier extends NonEmptyStringLiteral implements \JsonSerializable
{
    abstract public static function getPrefix(): string;

    public function toUnprefixedString(): string
    {
        return str_replace($this::getPrefix(), '', (string) $this);
    }

    #[\Override]
    protected function validate(string $value): void
    {
        parent::validate($value);

        if ('' !== static::getPrefix() && !str_starts_with($value, static::getPrefix())) {
            throw new \InvalidArgumentException('Identifier does not start with prefix "'.static::getPrefix().'", got: '.$value);
        }
    }

    public static function fromUnprefixed(string $unprefixed): static
    {
        return static::fromString(static::getPrefix().$unprefixed);
    }

    public static function fromOptionalUnprefixed(?string $unprefixed): ?static
    {
        if (is_null($unprefixed)) {
            return null;
        }

        return static::fromUnprefixed($unprefixed);
    }

    public static function random(): static
    {
        return static::fromString(static::getPrefix().RandomUuidFactory::random());
    }
}
