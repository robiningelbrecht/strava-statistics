<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

use Respect\Validation\Validator as v;

final readonly class Url extends NonEmptyStringLiteral
{
    #[\Override]
    protected function validate(string $value): void
    {
        parent::validate($value);
        if (!v::url()->validate($value)) {
            throw new \InvalidArgumentException(sprintf('Invalid url "%s"', $value));
        }
    }
}
