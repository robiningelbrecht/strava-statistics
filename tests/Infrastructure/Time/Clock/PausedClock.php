<?php

namespace App\Tests\Infrastructure\Time\Clock;

use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class PausedClock implements Clock
{
    private function __construct(private readonly SerializableDateTime $pausedOn)
    {
    }

    public static function on(SerializableDateTime $on): PausedClock
    {
        return new self($on);
    }

    public static function fromString(string $dateTime): PausedClock
    {
        return new self(SerializableDateTime::fromString($dateTime));
    }

    public function getCurrentDateTimeImmutable(): SerializableDateTime
    {
        return $this->pausedOn;
    }
}
