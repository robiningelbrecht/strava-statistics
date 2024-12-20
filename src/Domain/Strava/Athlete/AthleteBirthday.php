<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class AthleteBirthday extends SerializableDateTime
{
    #[\Override]
    public static function fromString(string $string): self
    {
        return new self($string);
    }
}
